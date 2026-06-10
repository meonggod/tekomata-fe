---
name: '[STORY] Upgrade catalog import: Excel default, async staging, explicit apply, failure-safe commit & history'
priority: 2
status: done
tags:
    - catalog
    - import
    - excel
    - websocket
    - async
    - staging
    - failure-handling
---

The catalog import is upgraded end-to-end: Excel (.xlsx) is the default format (CSV still
accepted), uploads are processed asynchronously with live WebSocket progress, and **nothing hits
the real catalog tables until the owner takes an explicit "Apply" action** to create the import.
After parsing, the job enters a staging state — conflicts are grouped by unique entity (e.g. one
decision for "create Gudang Surabaya" covers all 300 rows referencing it), error rows are always
skipped and shown for visibility only. The owner can **automate the apply step with an
"Auto-apply when no errors" checkbox that can be set at upload _or_ toggled while the job is still
running**, so a clean file commits with no manual step. Crucially, **the apply step itself can fail
at the database** (a duplicate, a unique/foreign-key constraint, a concurrent import) — those
failures are caught per row, recorded with their reason, and never abort the rest of the commit;
the job ends in a `partial` state with the failed rows retained so the owner can see why and retry
just those (or discard them). Past imports are visible in a history log; a clean apply or a discard
cleans up staged rows so the table stays lean.

> **Hard dependency:** the private GCS storage story must be in place before this story ships —
> the import worker stores uploaded files in the private GCS bucket before processing.

## Backend
**Goal:** accept uploads asynchronously, stage parsed rows, group conflicts by unique entity,
manage decisions at the conflict level, commit on explicit (or auto) apply with **per-row,
failure-safe inserts**, and push live events over WebSocket.

### Schema: `import_jobs`, `import_job_conflicts`, `import_staged_rows` — _engineer_
- **Goal:** the three-table model backing the full async staging + failure-safe apply lifecycle.
- **Background:**
- `import_jobs` — one row per upload: `id`, `company_id`, `uploaded_by` (user id),
`original_filename`, `format` (xlsx/csv), `file_path` (GCS), `auto_apply` (bool, **mutable**
while the job is pre-apply), `status` (queued/parsing/staged/applying/done/**partial**/failed/
discarded), `staged_at`, `applied_at`, `imported_count`, `updated_count`, `skipped_count`,
**`failed_count`**, `error_message`, `created_at`.
- `import_job_conflicts` — one row per **unique new entity** detected in a job (not per row):
`id`, `job_id`, `conflict_type` (new_warehouse / new_price_tier), `conflict_key` (entity
name, e.g. "Gudang Surabaya"), `affected_row_count`, `decision` (pending/skip/create).
Decision here covers every staged row that references this entity.
- `import_staged_rows` — one row per parsed file row: `id`, `job_id`, `row_number`,
`raw_data` (JSON of parsed fields), `status` (**ok / conflict / error / applied / failed**),
`conflict_id` (FK to `import_job_conflicts`, null for ok/error rows), `error_message` (for
error rows at staging **and** for rows that failed at commit time).
- **Cleanup policy:** on a fully clean apply or on discard, delete all `import_staged_rows` and
`import_job_conflicts` for the job. **On a `partial` apply (some rows failed to insert), retain
the `status: failed` rows (with their DB error) so the owner can review/retry them; everything
else is cleaned up.** On a catastrophic `failed` apply (nothing committed), retain the staged
rows so the whole job can be retried. The `import_jobs` row is always kept with its final counts
for history. **The GCS file is retained for the entire lifecycle (queued → done/partial/failed)
and removed only on discard (async)** — so any reparse/retry can re-read the original upload.

### `POST /catalog/import` — enqueue — _engineer_
- **Goal:** accept the file, validate the format, **persist the raw file to GCS first**, then
enqueue the job.
- **Background:** returns `{job_id}` immediately — no blocking work in the handler. Accepts
`.xlsx` (default) and `.csv`; rejects anything else **before** writing to GCS. **The upload is
written to the private GCS bucket and its `file_path` persisted on the `import_jobs` row before
the job is enqueued — GCS-first, always.** Processing never runs against the in-memory request
body; the worker always reads from the stored GCS object, so a worker that picks the job up
later (or re-runs it) operates on the durable copy, not the original HTTP upload. Accepts an
`auto_apply` boolean in the multipart body (the initial value of the mutable flag). Writes the
`import_jobs` row at `status: queued` only after the file is safely in GCS.

### `POST /catalog/import/:job_id/auto-apply` — toggle automation mid-flight — _ai-agent_
- **Goal:** let the owner turn "auto-apply when no errors" on or off **while the job is still
running**, so they can automate without having decided at upload time.
- **Background:** payload `{auto_apply: bool}`. Allowed only while the job is `queued`, `parsing`,
or `staged` (not after apply starts). If the job is **already `staged` and clean** (zero
conflicts, zero errors) and auto-apply is switched on, this triggers the apply worker
immediately. Scoped to the caller's active company.

### Import worker — parse and stage — _engineer_
- **Goal:** parse the file into staged rows, group conflicts by unique entity, and decide
whether to auto-apply.
- **Background:** picks up `queued` jobs; emits `import.parsing`. **Reads the file from GCS using
the job's `file_path`** (never the original request body) and runs the format-appropriate
parser — so the job is reprocessable from the durable copy. For each row, classifies as: `ok`
(clean upsert), `conflict` (references a warehouse
or price tier name not yet in the company's data), or `error` (missing SKU, non-numeric
quantity/price). Conflict rows are linked to an `import_job_conflicts` record — one record
per unique `(conflict_type, conflict_key)` pair, shared by all rows that reference the same
entity. Error rows carry an `error_message`; they are always skipped at apply time and require
no decision. After staging, emits `import.staged` with `{ok_count, conflict_count,
error_count, needs_review, concurrent_staged_jobs: [{job_id, filename}]}` — the
`concurrent_staged_jobs` field lists other jobs for this company currently in `staged` status
so the frontend can show a soft concurrency warning. **Auto-apply:** reads the job's current
(possibly toggled) `auto_apply` value; if `true` and `conflict_count=0` and `error_count=0`,
bypass review and trigger the apply worker immediately.

### Excel import parser — _ai-agent_
- **Goal:** parse an `.xlsx` file into raw row records for the staging worker.
- **Background:** reads the first sheet; row 1 is the header — columns matched by name, not
position. Produces one record per data row with all parsed fields. Does not classify or
validate — that is the staging worker's job. Pure transform, no DB or HTTP.

### Import apply worker — failure-safe commit — _engineer_
- **Goal:** commit all non-skipped staged rows into the real catalog tables **row by row without
letting one bad insert abort the rest**, then settle the job's final state and clean up.
- **Background:** triggered by `POST /:job_id/apply`, the auto-apply path, or `POST /:job_id/retry`.
Sets job to `applying`; emits `import.applying`.
- **Entities first, get-or-create:** for each `import_job_conflicts` row with `decision: create`,
create the entity (warehouse / price tier with **name only** — the owner completes location and
other details later in settings). Creation is **get-or-create**: if the name already exists
(e.g. created by a concurrent import or a prior partial run), resolve to the existing entity
instead of failing.
- **Rows, isolated per row:** process `import_staged_rows` — `ok` rows and `conflict` rows whose
conflict resolved to `create` are upserted **by SKU** (idempotent, so re-apply/retry never
duplicates). `conflict` rows resolved to `skip` and all `error` rows count as skipped. **Each
row commits in its own boundary: a DB error (duplicate key, unique/FK constraint, concurrent
write, etc.) is caught, the row is marked `status: failed` with the DB error in
`error_message`, `failed_count` is incremented, and the worker continues with the next row** —
a single failure never rolls back or aborts the already-committed rows.
- **Final state:** `failed_count == 0` → `done`; some rows committed and some failed → **`partial`**;
nothing could be committed at all (or the worker itself crashed / DB unreachable) → `failed`.
- **Cleanup:** on `done`, delete all staged rows + conflicts. On `partial`, delete everything
**except** the `status: failed` rows (kept for review/retry). On `failed`, retain staged rows
for a full retry. Emit `import.done` (clean), `import.partial` (with `failed_count`), or
`import.failed` (reason) accordingly, each carrying the final counts.

### `POST /catalog/import/:job_id/decisions` — record conflict decisions — _ai-agent_
- **Goal:** accept the owner's decisions at the conflict level and persist them.
- **Background:** payload is `[{conflict_id, decision: "skip"|"create"}]`. Updates the
`decision` field on each `import_job_conflicts` row. One decision covers all staged rows that
share that conflict. Only allowed while job is `staged`. Scoped to the caller's active company.

### `POST /catalog/import/:job_id/apply` — the explicit "create" action — _ai-agent_
- **Goal:** the owner's deliberate action to commit the staged import once they are satisfied with
their decisions — the create-or-not gate.
- **Background:** validates that no `import_job_conflicts` rows remain at `decision: pending`
before enqueuing the apply worker. Returns 422 with the list of undecided conflicts if any
remain. Only allowed while job is `staged`. Scoped to the caller's active company.

### `POST /catalog/import/:job_id/retry` — re-attempt failed rows — _ai-agent_
- **Goal:** let the owner re-run the apply for the rows that failed to insert, after a transient
cause has cleared or a duplicate has been resolved.
- **Background:** allowed only for a `partial` job (or a catastrophic `failed` job, where it
retries everything). For a `partial` job it re-enqueues the apply worker against the retained
`status: failed` rows only; because row upserts are idempotent by SKU, retrying never duplicates
rows that already committed. **For a `failed` job that never got past parsing/staging, retry
re-reads the original file from GCS and reparses it** — no re-upload needed, since the file was
stored GCS-first. A retry that clears all failures transitions the job to `done` and cleans up
the retained rows. Scoped to the caller's active company.

### `DELETE /catalog/import/:job_id` — discard — _ai-agent_
- **Goal:** let the owner abandon a staged import, or dismiss the retained failures of a `partial`
job, without committing (or re-committing) anything.
- **Background:** for a `staged` job, sets `discarded` and deletes its staged rows + conflicts. For
a `partial`/`failed` job, deletes the retained `status: failed` rows and leaves the job in its
final state (its committed counts stand). GCS file cleanup is async. Scoped to caller's active
company.

### `GET /catalog/import/history` — list past jobs — _ai-agent_
- **Goal:** return the import history for the active company.
- **Background:** lists `import_jobs` newest first: `job_id`, `original_filename`, `format`,
`status`, `imported_count`, `updated_count`, `skipped_count`, **`failed_count`**, `uploaded_by`
(display name), `created_at`. Jobs in `staged` status include a flag so the frontend can offer a
"Resume review" action; jobs in `partial`/`failed` status include a flag so it can offer a
"Retry failed" action. Paginated.

### `GET /catalog/import/:job_id/staged` — get staged / failed data for review — _ai-agent_
- **Goal:** return the rows the owner needs to act on so the frontend can render the review panel.
- **Background:** for a `staged` job, returns `import_job_conflicts` (with `affected_row_count` and
current `decision`) and `import_staged_rows` where `status: error` (with `row_number` and
`error_message`); OK rows are omitted (count only). For a `partial`/`failed` job, returns the
retained `status: failed` rows with `row_number` and the DB `error_message` so the owner can see
why each failed. Scoped to caller's active company.

### WebSocket hub — import channel — _engineer_
- **Goal:** push import job lifecycle events to the owner in real time.
- **Background:** channel scoped to `company_id + job_id` (`GET /ws/import/:job_id`). Events:
`import.queued`, `import.parsing`, `import.staged` (summary + `concurrent_staged_jobs`),
`import.applying`, `import.done` (final counts), **`import.partial`** (final counts +
`failed_count`), `import.failed` (reason). On reconnect, the current job state is sent
immediately so the UI can reconstruct without missing events.

### Excel template endpoint — _ai-agent_
- **Goal:** serve a downloadable `.xlsx` template with the correct column headers.
- **Background:** static or generated file served from the Go API. Server-side so the column
set can change without a frontend redeploy.

## Frontend
**Goal:** non-blocking import UX inside the Product list page — upload with auto-apply checkbox,
live job tracker (with a mid-flight automation toggle), conflict review panel grouped by unique
entity, a failed-rows panel with retry, and import history.

### Product list controller — import actions — _ai-agent_
- **Goal:** handle upload, auto-apply toggle, decision submission, apply, retry, and discard.
- **Background:** POST/DELETE actions on the existing product list controller, each forwarding to
the Go API: enqueue (file + initial `auto_apply`), auto-apply toggle (`{auto_apply}`), decisions
(`[{conflict_id, decision}]`), apply, retry, discard. Validate accepted MIME types before
forwarding. All actions are non-blocking — the response carries `job_id` or redirects back to the
product list; no waiting on import completion.

### Product list view — Import button + live job tracker — _engineer_
- **Goal:** upload panel with live per-job progress and a mid-flight automation toggle; supports
multiple concurrent jobs.
- **Background:** Import button in the top-right of the product table header. Opens an inline
panel with: file input (`.xlsx` first, `.csv` accepted), template download link, Excel-format
hint, and an **"Auto-apply when no errors"** checkbox. On submit, the job appears in a list
at `queued` and a WebSocket connection opens. Jobs show status badges
(`queued → parsing → staged → applying → done / partial / failed`). **While a job is still
pre-apply, the auto-apply checkbox stays editable on the job row and calls the toggle action** so
the owner can automate after upload. When a job reaches `staged` with `needs_review: true`, a
**Review** button appears; when it ends `partial`/`failed`, a **Retry failed** button appears.
When `concurrent_staged_jobs` is non-empty, show a soft warning: "You have another import pending
review — applying both may overwrite overlapping products." Multiple jobs stack in the list.

### Staged review panel — conflict resolution + failed rows — _engineer_
- **Goal:** let the owner resolve conflicts grouped by unique entity before applying, and review
rows that failed to commit afterward.
- **Background:** opens inline (or as a side drawer) when the owner clicks Review (staged) or Retry
(partial). Sections:
- **OK** — collapsed by default, shows row count only.
- **Conflicts** — one card per unique conflict entity (e.g. "New warehouse: Gudang Surabaya —
affects 47 rows"). Each card has a **Create** / **Skip** toggle; "Create" notes the entity is
created with name only and details can be filled in settings. A **Decide all** bulk toggle
resolves all conflicts at once.
- **Errors** — rows with bad data; always skipped. Shows row number and error message for
visibility. Collapsed list with a count header.
- **Failed (apply-time)** — shown for `partial`/`failed` jobs: rows that the database rejected on
commit, each with its row number and the DB reason (e.g. "duplicate SKU", "constraint
violation"). Offers **Retry failed** and **Dismiss** (discard the retained failures).
- Summary bar: "X conflicts unresolved" — counts down as decisions are made. **Apply** (the
explicit create action) activates only when unresolved count reaches zero. **Discard** abandons
the import.

### Import history section — _engineer_
- **Goal:** let the owner see past imports and resume pending reviews or retry failures.
- **Background:** collapsible list below the job tracker (or a separate tab). Each row: filename,
format badge, status, counts (imported / updated / skipped / **failed**), uploader, date. Jobs in
`staged` status show a **Resume review** button; jobs in `partial`/`failed` status show a
**Retry failed** button that reopens the failed-rows panel. `done`/`discarded` jobs are read-only
count summaries.

## Acceptance criteria
- [ ] Uploading a file returns a `job_id` immediately — the HTTP request does not block on
parsing or import.
- [ ] The raw upload is written to the private GCS bucket **before** the job is enqueued, and its
`file_path` is stored on the job; processing always reads from GCS, never the request body.
- [ ] The GCS file is retained through the whole lifecycle, so a failed parse/staging/apply can be
reprocessed or retried without the owner re-uploading; it is removed only on discard.
- [ ] The owner sees live status updates via WebSocket without polling.
- [ ] Nothing is written to the real catalog tables until the owner takes an explicit **Apply**
action — unless auto-apply is enabled.
- [ ] The "Auto-apply when no errors" checkbox can be set at upload **and** toggled while the job
is still `queued`/`parsing`/`staged`; switching it on for an already-`staged` clean job
applies immediately.
- [ ] With auto-apply on and zero conflicts/errors, the import applies automatically — no review
step is shown.
- [ ] With auto-apply on but conflicts or errors present, the import pauses at `staged` and
requires review.
- [ ] Conflicts are grouped by unique entity — one Create/Skip decision covers all rows that
reference the same new warehouse or price tier; a "Decide all" bulk toggle is available.
- [ ] "Create" on a new warehouse/price tier creates it with name only, via **get-or-create** — an
already-existing name resolves to the existing entity instead of failing the apply.
- [ ] Error rows (missing SKU, bad numeric) are always skipped at staging; shown for visibility,
no decision required.
- [ ] Apply is blocked until all conflict cards have a decision; the button shows the unresolved
count.
- [ ] **During apply, a row that fails to insert (duplicate, unique/FK constraint, concurrent
write, etc.) is caught and recorded with its DB error; it does not roll back or abort the
rows that already committed.**
- [ ] A job that commits some rows and fails others ends as **`partial`** (not `done`, not
`failed`) with imported / updated / skipped / failed counts.
- [ ] A job where nothing could be committed (or the worker crashed / DB unreachable) ends as
`failed`, and its staged rows are retained so it can be retried.
- [ ] The owner can see exactly which rows failed at apply and why, and can **retry just the failed
rows** or dismiss them.
- [ ] Re-applying or retrying is idempotent — row upserts are keyed by SKU, so already-committed
rows are never duplicated.
- [ ] After a clean apply (`done`) or a discard, all staged rows and conflict records are deleted;
after a `partial`, only the failed rows are retained until they are retried or dismissed.
- [ ] The `import_jobs` row is always kept with final counts for history.
- [ ] When another staged import for the same company exists, a soft warning is shown before the
owner applies — no hard block.
- [ ] A `staged` job can be resumed from history to complete a deferred review; a `partial`/`failed`
job can be retried from history.
- [ ] Multiple files can be queued and tracked concurrently, each with its own status.
- [ ] On WebSocket reconnect, the current job state is delivered immediately.
- [ ] Excel (.xlsx) and CSV are accepted; any other format is rejected before queuing.
- [ ] Columns are matched by header name — column order does not matter.
- [ ] The downloadable template is `.xlsx` with the correct column headers.
- [ ] All catalog data remains isolated per company throughout the staging, apply, and retry
lifecycle.

---

## API contract — frontend handoff (build in parallel)

> **Realtime note:** the task wording says "WebSocket"; this repo's only realtime transport is **SSE**.
> Live progress is delivered over SSE at `GET /api/v1/ws/import/{job_id}` (server→client push +
> immediate state-on-reconnect — every acceptance criterion is met). Full contract + Mermaid in
> `documentation/features/catalog-import-async.md`; executable source of truth in Postman (folder
> *Catalog import (async)*). All endpoints JWT-only, tenant-scoped via the Principal.

| Method | Path | Purpose | Success |
|--------|------|---------|---------|
| POST | `/api/v1/catalog/import` | enqueue upload (`multipart`: `file`, `auto_apply`) | `202 { job_id, status: queued }` |
| POST | `/api/v1/catalog/import/{job_id}/auto-apply` | toggle auto-apply mid-flight `{auto_apply}` | `200 { ...job }` |
| POST | `/api/v1/catalog/import/{job_id}/decisions` | `[{conflict_id, decision}]` | `204` |
| POST | `/api/v1/catalog/import/{job_id}/apply` | explicit commit | `202 { job_id, status: applying }` |
| POST | `/api/v1/catalog/import/{job_id}/retry` | re-attempt failed rows / reparse | `202 { job_id }` |
| DELETE | `/api/v1/catalog/import/{job_id}` | discard / dismiss failures | `204` |
| GET | `/api/v1/catalog/import/history?limit=&offset=` | past jobs | `200 { jobs:[...] }` |
| GET | `/api/v1/catalog/import/{job_id}/staged` | review-panel data | `200 { job, conflicts, error_rows, failed_rows }` |
| GET | `/api/v1/catalog/import/template` | `.xlsx` template download | `200` (binary) |
| GET | `/api/v1/ws/import/{job_id}` | live progress (SSE) | `200 text/event-stream` |

Errors: `import.job_not_found` (404), `import.invalid_state` (409), `import.unsupported_format` (422),
`import.conflicts_pending` (422), `import.parse_failed` (400), `file.too_large` (400),
`file.storage_unavailable` (500 when no GCS bucket configured). SSE events: `import.queued|parsing|staged|
applying|done|partial|failed`.

**Spreadsheet columns** (matched by name, order-independent): fixed `sku, name, unit, is_fractional,
currency_code, default_price`; dynamic `stock:<warehouse>` and `price:<tier>`.

## Implementation notes (done)
- Migration `00015` (`import_jobs`, `import_job_conflicts`, `import_staged_rows`); queries in
  `db/queries/import.sql`; sqlc-generated. ERD updated.
- Layers: `domain/catalog` (`import_job.go` — entities/enums/ports), `usecase/catalog`
  (`import_async.go` service, `import_worker.go` stage+apply worker + pure `classifyRows`/`settleApply`),
  `resource/sheet` (excelize xlsx + csv parser + `.xlsx` template — **only** package importing excelize),
  `resource/postgres/import_repo.go` (incl. per-row failure-safe `ApplyRow`), `resource/gcs/import_store.go`
  (adapter over the private bucket), `resource/realtime/import_hub.go` (SSE), `resource/http/handler/
  catalog_import_async.go`, routes + `main.go` wiring (worker via `safego`, 2s poll).
- The old synchronous `POST /catalog/import` is superseded; `GET /catalog` browse is unchanged.
- Tests: `import_worker_test.go` (classification + settle), `sheet/parser_test.go` (csv/xlsx/template).
  `go build ./...`, `go vet ./...`, `go test ./...` all green. (Lint toolchain is independently broken vs
  Go 1.25 — verified via build/vet/test.)
