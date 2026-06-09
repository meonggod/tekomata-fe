---
name: '[STORY] Post-login KYC/KYB onboarding'
priority: 3
status: done
tags:
    - compliance
    - kyb
    - kyc
    - onboarding
---

After a user first verifies and logs in, they are guided through a one-time onboarding that
collects their personal identity (**KYC**) and their business details (**KYB**). Until it's
complete the core dashboard is gated, so every active account has real identity behind it. This
is where the placeholder company created at register gets its real business profile.

## Backend
**Goal:** model the KYC (person) and KYB (business) data plus a completion status, and expose
endpoints to read what's still required and submit it.

### KYC/KYB schema + completion status — _engineer_
- **Goal:** persist a user's KYC identity, a company's KYB business profile, and a per-company
onboarding status.
- **Background:** KYC hangs off the `users` row (the person — collected once per user); KYB hangs
off the `companies` row (the business profile, replacing the placeholder name set at register).
A per-company status (e.g. incomplete → submitted → complete) drives the dashboard gate.
Sensitive identity data is stored securely.

### `GET /onboarding/requirements` — _ai-agent_
- **Goal:** tell the client what's still needed and whether the account is gated.
- **Background:** returns the missing/required KYC and KYB fields and the current status, so the UI
renders the right step and the gate knows when to lift. Scoped to the active company.

### `POST /onboarding/kyc` + `POST /onboarding/kyb` — _engineer_
- **Goal:** accept and persist the submitted personal (KYC) and business (KYB) details.
- **Background:** validates the required set; KYC updates the user's identity, KYB updates the
active company's profile (name, business type, registration/tax id, address). Flips the
company's status to complete once both required sets are satisfied.

## Frontend
**Goal:** a guided post-login onboarding flow that collects KYC + KYB and unlocks the dashboard.

### Onboarding gate + routing — _ai-agent_
- **Goal:** route users with incomplete onboarding into the flow and keep them out of the core
dashboard until done.
- **Background:** checks `/onboarding/requirements` after login and redirects to onboarding while
the active company's status is incomplete.

### KYC + KYB onboarding forms — _engineer_
- **Goal:** the multi-step UI that collects the person's identity, then the business profile.
- **Background:** posts to the KYC/KYB endpoints, shows progress and which fields remain, and on
completion routes to the dashboard.

## Acceptance criteria
- [x] After first login, a user whose active company's onboarding is incomplete is routed into
onboarding and cannot reach the core dashboard. (Backend: `GET /onboarding/requirements` returns
`gated:true` until both sides complete — the FE gate. Verified live.)
- [x] The flow surfaces the required KYC (personal identity) and KYB (business profile) fields and
which are still missing (`missing[]` per side).
- [x] Submitting valid KYC persists it against the user; submitting valid KYB updates the active
company's profile, replacing the placeholder company name. (Verified: `company.name` → `legal_name`.)
- [x] Invalid or missing required fields are rejected with clear inline errors (`422` +
per-field `fields[]`) and don't advance the flow.
- [x] Once KYC and the active company's KYB are both complete, the company's status flips to
complete and the dashboard unlocks (`gated:false`). (Verified live.)
- [x] A user whose onboarding is already complete skips the flow (requirements returns `gated:false`).
- [x] KYB is per company (1:1 `company_id`); KYC is per user (1:1 `user_id`) — completing KYB for
one company doesn't affect another the user owns.
- [x] Sensitive identity data (`id_number`, `tax_id`) is stored securely and never appears in
plaintext logs or responses — **masked** on read (`••••0001`), **not logged** (grep of the live
server log for the raw values = 0). *Column-level encryption-at-rest deferred (no key-mgmt infra);
agreed during review.*

> **Frontend (this repo) — done.**
> - `EnsureOnboarded` middleware (`ensure.onboarded` alias): checks `/onboarding/requirements` once per session and redirects gated users to the onboarding flow. Gate flag cached in session; lifted when KYB succeeds.
> - `OnboardingController`: `/onboarding` (router), `/onboarding/kyc` (GET+POST), `/onboarding/kyb` (GET+POST). Routes sit inside `auth.api` but outside `ensure.onboarded` to prevent redirect loops.
> - `OnboardingApi` service: thin wrappers for the three onboarding endpoints.
> - Two-step Blade forms (`resources/views/onboarding/kyc.blade.php`, `kyb.blade.php`) with progress indicator, per-field inline errors, and `old()` repopulation.
> - All strings in `lang/en/messages.php` + `lang/id/messages.php` under `onboarding.*`.

## Implementation notes (done)
- Migration `00009_kyc_kyb.sql`: `kyc_profile` (UNIQUE `user_id`) + `kyb_profile`
  (UNIQUE `company_id`), each with a `status` column.
- `domain/onboarding` (entities + `OnboardingRepository` port) → `usecase/onboarding`
  (validation + computed gate, tested) → `postgres/onboarding_repo.go` (KYB upsert + `company.name`
  rewrite in one tx) → `handler/onboarding.go` (masks PII on output) + 3 routes + wiring.
- Completion is **computed** per request (KYC complete AND KYB complete → gate lifts), not a stored
  cross-entity flag. Submissions are idempotent upserts.
- `go vet` + `go build` + `go test ./...` green. (`make lint`/`-race` blocked by the pre-existing
  env issue noted in the catalog-import task.)

---

## API contract — frontend handoff (build in parallel)

> Full design + diagrams: `documentation/features/kyc-kyb-onboarding.md`.
> Executable source of truth: Postman → folder **"Onboarding"**.
> All routes: `/api/v1`, **Bearer JWT**, tenant-scoped (user + company from the Principal).
> PII (`id_number`, `tax_id`) is **masked** in every response and never logged.

**Completion model:** KYC is 1:1 with the user; KYB is 1:1 with the company (with its own status).
Overall onboarding is `complete` (gate lifts) iff the user's KYC is complete AND the active
company's KYB is complete — computed per request, not stored as one cross-entity flag.

### `GET /onboarding/requirements` → `200`
The gate the FE checks after login.
```json
{ "data": {
  "gated": true,
  "kyc": { "status": "incomplete", "missing": ["full_name","id_number","date_of_birth","id_type","address"] },
  "kyb": { "status": "incomplete", "missing": ["legal_name","business_type","registration_number","tax_id","address"] }
} }
```

### `POST /onboarding/kyc` → `200`  (per user, upsert)
Required: `full_name`, `date_of_birth` (epoch ms), `id_type`, `id_number`, `address`.
```json
{ "full_name": "Reynaldi Setiadi", "date_of_birth": 631152000000, "id_type": "ktp",
  "id_number": "3174012345670001", "address": "Jl. Sudirman 1, Jakarta" }
```
`200` (id_number masked):
```json
{ "data": { "status": "complete", "full_name": "Reynaldi Setiadi", "date_of_birth": 631152000000,
  "id_type": "ktp", "id_number": "••••0001", "address": "Jl. Sudirman 1, Jakarta",
  "updated_at": 1781000000000 } }
```
`422`:
```json
{ "error": { "code": "validation_failed", "message": "one or more fields are invalid",
  "request_id": "c0ffee…",
  "fields": [ { "field": "id_number", "code": "validation.required", "message": "is required" } ] } }
```

### `POST /onboarding/kyb` → `200`  (per active company, upsert; overwrites company.name)
Required: `legal_name`, `business_type`, `registration_number`, `tax_id`, `address`.
```json
{ "legal_name": "PT Tekomata Niaga", "business_type": "pt", "registration_number": "AHU-0012345",
  "tax_id": "01.234.567.8-901.000", "address": "Jl. Sudirman 1, Jakarta" }
```
`200` (tax_id masked):
```json
{ "data": { "status": "complete", "legal_name": "PT Tekomata Niaga", "business_type": "pt",
  "registration_number": "AHU-0012345", "tax_id": "••••.000", "address": "Jl. Sudirman 1, Jakarta",
  "updated_at": 1781000000000 } }
```
