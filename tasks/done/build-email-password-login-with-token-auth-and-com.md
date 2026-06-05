---
name: '[STORY] Build email + password login with token auth and company switching'
priority: 2
status: done
tags:
    - auth
    - login
    - security
---

A user signs in from a Laravel login page with their email and password, optionally ticking
"remember me". On success the Go API issues a short-lived access token and a longer-lived refresh
token, scoped to one active company; the Laravel app keeps the user signed in, silently refreshes
the access token when it expires, and lets a user who belongs to several companies switch between
them.

## Backend
**Goal:** authenticate a verified user by email + password and issue an access/refresh token pair
scoped to an active company. (The identity schema — users, companies, memberships — is created in
the register story; this story builds on it.)

### `POST /auth/login` — _engineer_
- **Goal:** verify credentials and mint the access + refresh token pair.
- **Background:** accepts an email, password, and a remember-me flag that lengthens the
refresh-token lifetime. Only **verified** users may sign in. The token is scoped to an active
company — selected automatically if the user has exactly one, otherwise the default/most recent.
Failures return a generic error (don't reveal whether the email exists).

### `POST /auth/refresh` — _engineer_
- **Goal:** exchange a valid refresh token for a new access + refresh pair.
- **Background:** refresh tokens rotate (single-use) so reuse can be detected; revoked/expired
tokens are rejected.

### `POST /auth/logout` + access-token middleware — _ai-agent_
- **Goal:** let a user sign out and let other endpoints require a valid access token.
- **Background:** logout revokes the refresh token; the middleware validates the access token and
exposes the **user id and active company id** to downstream handlers so every request is
tenant-scoped.

### List companies + switch active company — _engineer_
- **Goal:** let a user who belongs to several companies see them and change which one is active.
- **Background:** lists the caller's company memberships; switching re-issues the token scoped to
the chosen company so every later request is scoped to the active company carried in the token.

## Frontend
**Goal:** a Laravel login page that signs the user in and keeps the session fresh.

### Login route + controller — _ai-agent_
- **Goal:** render the login page and handle the credential submission.
- **Background:** posts to the Go `/auth/login`; on success the tokens are stored server-side
(httpOnly), never in JS-readable storage.

### Login form view — _engineer_
- **Goal:** the login UI — email, password, and remember-me.
- **Background:** an email input feeds the API identifier; the checkbox maps to remember-me.
Auth/validation errors show inline.

### Token storage + silent refresh — _engineer_
- **Goal:** keep the user authenticated across requests and refresh transparently.
- **Background:** when the short-lived access token expires the app should refresh once and retry,
only forcing re-login if the refresh fails.

### Company switcher — _ai-agent_
- **Goal:** let a multi-company user change the active company from the dashboard.
- **Background:** shows the user's companies and calls the switch endpoint; on success the app
re-stores the new token and reloads in the chosen company's context. Hidden for single-company
users.

## Acceptance criteria  (backend; verified end-to-end against PostgreSQL)
- [x] A verified user can sign in with email + password; unverified users are refused
(they have no `user` row yet → generic `401 auth.invalid_credentials`).
- [x] Login returns a short-lived access token + longer-lived refresh token; remember-me
lengthens the refresh-token lifetime (30d → 90d, verified `refresh_expires_in=7776000`).
- [x] The access token is scoped to one active company — auto-selected when the user has exactly one.
- [x] A user in several companies can list them (`GET /auth/companies`) and switch the active
company (`POST /auth/switch-company`); switching re-issues the token scoped to the chosen company.
- [x] `/auth/refresh` rotates refresh tokens (single-use) and rejects revoked, expired, or reused
tokens; **reuse revokes the whole session** (verified: rotated-after-reuse token also 401).
- [x] Logout revokes the refresh token (`204`, idempotent); protected endpoints reject requests
lacking a valid access token (`401 unauthorized`) and expose user id + active company id via the Principal.
- [x] Invalid credentials return a generic error that doesn't reveal whether the email exists.
- [x] _Frontend (separate repo):_ tokens stored httpOnly server-side; access token refreshes silently.

> **Frontend (this repo) — done.** All `## Frontend` units implemented against this (re-exported,
> backend-verified) contract — unchanged from the prior build: login route + controller
> (`/auth/login`, generic catalog-rendered errors), login form view (email + password + remember-me,
> inline errors), token storage server-side in the session + silent single refresh on expiry
> (`TokenStore` + `EnsureAuthenticated`), logout revoking the refresh token upstream, and the
> dashboard company switcher (hidden for single-company users). The backend criteria above assert
> **Go API** behaviour and were verified end-to-end by the backend repo.

---

## API contract — frontend handoff (build in parallel)

> Backend-only repo. **Executable source of truth:** Postman → *API v1 / Auth*.
> **Design + diagrams:** `documentation/features/auth-login.md`.
> **Error codes → i18n (EN/ID):** `documentation/error-catalog.md`.
> Envelope: `{ "data": ... }` / `{ "error": { "code", "message", "request_id", "fields"? } }`;
> `code` is an i18n key (render from it, not `message`). `expires_in` is in seconds.

**Token pair** (returned by login, refresh, switch-company):
```json
{ "data": {
  "access_token": "eyJhbGci...", "token_type": "Bearer", "expires_in": 900,
  "refresh_token": "kQ8b...", "refresh_expires_in": 7776000,
  "active_company_id": "7a2c...uuid"
} }
```

### `POST /api/v1/auth/login` — public
Request: `{ "email": "owner@acme.com", "password": "an0therStr0ng!pass", "remember_me": true }`
Success `200`: token pair (above).
Errors:
- `401 auth.invalid_credentials` (unknown email OR wrong password — generic):
  ```json
  { "error": { "code": "auth.invalid_credentials", "message": "invalid email or password", "request_id": "host/abc-1" } }
  ```
- `403 auth.email_not_verified` (password ok but unverified):
  ```json
  { "error": { "code": "auth.email_not_verified", "message": "email not verified", "request_id": "host/abc-1" } }
  ```
- `422 validation_failed` (missing/invalid fields, with `fields[]`).

### `POST /api/v1/auth/refresh` — public
Request: `{ "refresh_token": "kQ8b..." }`
Success `200`: a NEW token pair (old refresh is revoked — single use).
Error `401 auth.invalid_refresh_token` (unknown / expired / revoked / **reused** → whole session revoked):
```json
{ "error": { "code": "auth.invalid_refresh_token", "message": "invalid or expired refresh token", "request_id": "host/abc-2" } }
```

### `POST /api/v1/auth/logout` — public
Request: `{ "refresh_token": "kQ8b..." }` → `204 No Content` (idempotent).

### `GET /api/v1/auth/companies` — Bearer JWT
Success `200`:
```json
{ "data": { "active_company_id": "7a2c...uuid", "companies": [
  { "company_id": "7a2c...uuid", "name": "Acme", "role": "owner" }
] } }
```
Error `401 unauthorized`:
```json
{ "error": { "code": "unauthorized", "message": "authentication required", "request_id": "host/abc-3" } }
```

### `POST /api/v1/auth/switch-company` — Bearer JWT
Request: `{ "company_id": "9f1d...uuid" }`
Success `200`: new token pair (scoped to the chosen company).
Errors: `403 forbidden` (not a member):
```json
{ "error": { "code": "forbidden", "message": "you do not have access to this resource", "request_id": "host/abc-4" } }
```
plus `401 unauthorized`, `422 validation_failed` (`company_id` missing/not uuid).
