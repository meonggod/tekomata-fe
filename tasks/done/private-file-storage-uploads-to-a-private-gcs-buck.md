---
name: '[STORY] Private file storage: uploads to a private GCS bucket served through a tekomata proxy'
priority: 2
status: done
tags:
    - storage
    - gcs
    - uploads
    - infrastructure
    - multi-tenant
---

Files users upload — product images, KYC/KYB documents, import sheets — are stored in a **private**
Google Cloud Storage bucket that is never publicly readable. Instead of handing out raw GCS URLs,
tekomata serves every file through its own proxy on a branded domain: a user only ever sees
`[storage.tekomata.com/](http://storage.tekomata.com/)<opaque-id>`, and the proxy authorizes each request before returning the bytes.
This is the shared storage layer the KYC/KYB, product, and catalog-import stories upload to.
**Backend only — it has no control-panel UI of its own; the upload widget and file rendering live in
those consuming stories, which call this service.**

## Backend
**Goal:** accept uploads into a private GCS bucket, track each object's owner + visibility, and serve
files back through an authorizing proxy on a branded domain — never exposing the raw GCS URL or the
bucket layout.

### File metadata schema — _engineer_
- **Goal:** the source of truth mapping a public, opaque id to a private GCS object and recording who
owns it.
- **Background:** a `files` table — an **opaque public id** (random, unguessable — the only thing in the
URL), the **GCS object path** (tenant-prefixed, e.g. `{company_id}/{uuid}`), the **owning company**
(tenant), the uploader (user or API key), original filename, content-type, size, checksum,
**visibility** (`public` / `private` for v1 — see the authorization unit; the field is an enum so an
`authenticated` tier can be added later without a schema change), and soft-delete + timestamps. The public URL
carries only the opaque id — never the filename, bucket, or path — so objects can't be enumerated.

### GCS storage adapter — _engineer_
- **Goal:** the single layer that talks to Google Cloud Storage (put, get/stream, delete).
- **Background:** mirrors the `internal/clickup` pattern — the **only** package that knows GCS wire
details; handlers/agent go through it, never importing GCS directly. The bucket is **private** (uniform
bucket-level access, no public objects). Service-account credentials come from config/env as a secret
(never committed); one client constructed from config, never a package-level global. **Uses the official
Google Cloud Storage Go client (`[cloud.google.com/go/storage`](http://cloud.google.com/go/storage`)) — the one sanctioned SDK exception in this
repo, because cloud-provider auth (service-account JWT signing) and signed URLs are security-sensitive and
should not be hand-rolled. This adapter is the only place the SDK is imported.**

### `POST /files` — upload a file — _engineer_
- **Goal:** accept an upload, validate it, store it privately, and return its branded URL.
- **Background:** scoped to the caller's active company (user session or API key). Validates
content-type against an allowlist and enforces a max size; stores the object under a tenant-prefixed key
with a fresh opaque id; writes the metadata row with the requested visibility. Returns the branded URL
(`[storage.tekomata.com/](http://storage.tekomata.com/)<id>`) plus content-type and size. Bytes flow through the API for v1 (files are
small); a signed **direct-to-GCS upload URL** is the later scale path for large files.

### `GET /files/{id}` — authorizing serve proxy ([storage.tekomata.com](http://storage.tekomata.com)) — _engineer_
- **Goal:** resolve the opaque id and return the file's bytes through the branded domain, after
authorizing the request.
- **Background:** the public read path behind `storage.tekomata.com`. Resolves the opaque id to its
metadata, **authorizes by visibility** (see the next unit), then returns the object. For v1 it
**streams the bytes from GCS** so the raw GCS URL is never exposed and access is checked on every
request; supports Range requests and sets correct `Content-Type`, caching headers (`public` cacheable;
`private` → `private, no-store`), and `ETag`. **The serve mechanism sits behind a small `FileServer`-style
interface (e.g. `Serve(ctx, object, w, r)`) with streaming as the v1 implementation, so it can later be
swapped for a 302-redirect-to-signed-URL or a Cloud CDN implementation to offload egress — with no change
to the handler or callers.**

### Visibility + tenant authorization — _ai-agent_
- **Goal:** decide whether a given request may read a given object, without leaking its existence.
- **Background:** `public` objects (e.g. catalog/product images) serve to anyone with the link, no login;
`private` objects (e.g. KYC/KYB docs) require a valid session or API key **whose active company matches
the object's owning company** — this is the tenant-isolation / IDOR guard. A request for an object the
caller may not see returns a **uniform not-found** (never "exists but forbidden", to avoid enumeration).
Sensitive docs (KYC/KYB) are `private` and never publicly cacheable. The model leaves room for an
`authenticated` tier later (any logged-in platform user, not tenant-restricted) if a concrete cross-tenant
case appears — not needed for v1.

### `DELETE /files/{id}` — remove a file — _ai-agent_
- **Goal:** delete an object the active company owns.
- **Background:** soft-deletes the metadata row and removes (or schedules removal of) the GCS object;
scoped to the owning company. Abandoned/orphaned uploads are swept by a GCS lifecycle rule.

> **Frontend (this repo) — nothing to build.** This is a backend-only storage layer with no
> control-panel UI of its own: no `## Frontend` units, no panel routes, views, or widget code.
> The upload widget and file rendering are owned by the consuming stories (KYC/KYB, product,
> catalog-import), which call this service via the Go API. The branded proxy URL
> (`storage.tekomata.com/files/<id>`) returned by `POST /api/v1/files` is consumed there. No
> frontend-owned acceptance boxes exist; the ticked criteria are all backend-owned and left as
> the backend set them.

## Acceptance criteria
- [x] Uploaded files land in a **private** GCS bucket that is never publicly readable; no object is
served via a raw `storage.googleapis.com` URL. *(adapter streams through `OpenRange`; only the proxy serves bytes)*
- [x] Every file is reachable only through the branded proxy as `storage.tekomata.com/<opaque-id>`; the
URL exposes neither the filename, the bucket, nor the object path. *(response carries only `public_id`)*
- [x] The opaque public id is random and unguessable — objects cannot be enumerated by walking ids.
*(128-bit crypto-random, base64url)*
- [x] Each object has a visibility of `public` (anyone with the link) or `private` (owning company only),
stored as text (NOT a DB enum) validated in the domain layer — extendable to an `authenticated` tier with **no schema change**.
- [x] Each object records an owning company; `private` objects are served only to a caller whose active
company matches the owner (tenant isolation / no IDOR), and an unauthorized request returns a uniform
not-found. *(pure `File.CanRead`; both miss + deny → `file.not_found`)*
- [x] `public` objects (e.g. product images) are served to anyone and may be cached; `private` objects
(e.g. KYC/KYB docs) are never publicly cached. *(`Cache-Control` public-immutable vs `private, no-store`)*
- [x] Uploads validate content-type against an allowlist and enforce a max size; rejected uploads return
a clear error. *(`file.unsupported_type` / `file.too_large`)*
- [x] Deleting a file removes (or schedules removal of) the underlying GCS object, after which the URL
stops resolving. *(soft-delete row + `store.Delete`; lifecycle rule sweeps orphans)*
- [x] GCS credentials are loaded from config/secret (never committed), and only the storage-adapter
layer talks to GCS. *(`storage.credentials_json` via `${ENV_VAR}`; SDK imported only in `resource/gcs`)*
- [~] Uploads and serving work for both a logged-in panel user and an API-key caller, attributed to the
owning company. **JWT shipped; API-key DEFERRED** — the API-key auth middleware is still a `todo/` stub.
Attribution flows through the `Principal` (`uploader_api_key_id` column + nullable FK already in place), so
API-key callers work with **no contract change** the moment that middleware lands. Lower-security-risk than
building a second, incomplete auth surface here.

---

## API contract — frontend handoff (build in parallel)

Mirror of `documentation/features/private-file-storage.md`; Postman folder **"Files"** is the executable
source of truth.

### `POST /api/v1/files` — upload  ·  Auth: Bearer JWT  ·  `multipart/form-data`
Parts: `file` (binary, required), `visibility` (`public`|`private`, default `private`).
Allowlist (default): jpeg/png/webp/gif, pdf, csv, xls/xlsx. Max 10 MiB (configurable).

**`201`**
```json
{ "data": { "id": "u3Jq8x2pQF6m0aZ1bC9dEg",
  "url": "https://storage.tekomata.com/files/u3Jq8x2pQF6m0aZ1bC9dEg",
  "filename": "passport.pdf", "content_type": "application/pdf", "size": 184213,
  "visibility": "private", "created_at": 1749600000000 } }
```
**Error** (`422`)
```json
{ "error": { "code": "file.unsupported_type", "message": "this file type is not allowed",
  "request_id": "b1f2…", "fields": [ { "field": "file", "code": "validation.unsupported_file_type" } ] } }
```
Other: `400 file.too_large`, `422 validation_failed` (missing part / bad visibility),
`401 unauthorized`, `500 file.storage_unavailable`.

### `GET /files/{id}` — serve proxy  ·  Auth: optional Bearer JWT  ·  root path (branded domain)
`200`/`206` raw bytes + `Content-Type`, `ETag`, `Accept-Ranges: bytes`, `Cache-Control`
(`public, max-age=31536000, immutable` | `private, no-store`); `304` on `If-None-Match`.
**Error** (`404`, uniform for missing AND unauthorized):
```json
{ "error": { "code": "file.not_found", "message": "file not found", "request_id": "b1f2…" } }
```

### `DELETE /api/v1/files/{id}` — delete  ·  Auth: Bearer JWT
**`204`** (idempotent). **Error** `404 file.not_found` (unknown / other-company), `401 unauthorized`.

---

## Implementation notes (decisions taken)
- **Layering:** `domain/file` (entity + `Visibility` + `Viewer.CanRead` rule + `Repository`/`ObjectStore`
  ports) ← `usecase/file.Service` (validate/store/authorize/delete) ← `resource/postgres.FileRepository`
  + `resource/gcs.Store` (the lone GCS SDK import) + `resource/http/handler.File` with a swappable
  `FileServer` (`StreamingFileServer` v1). Wired in `cmd/api/main.go`.
- **Migration `00014_file_storage.sql`** adds the `file` table (see ERD); `make sqlc` regenerated.
- **Body cap:** the global 1 MiB cap exempts `POST …/files`; the upload handler enforces
  `storage.max_upload_bytes` via `MaxBytesReader`.
- **Storage off in dev:** no bucket → a `Disabled` store keeps routes mounted and returns
  `file.storage_unavailable` (stable FE contract; `GCS only` per the chosen strategy — no local fallback).
- **Verified:** `go build ./...`, `go vet ./...`, `go test ./...` all pass (incl. new domain/usecase/handler
  unit tests). `make lint` skipped (known golangci-lint↔Go 1.25 toolchain break).
