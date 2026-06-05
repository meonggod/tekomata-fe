---
name: '[STORY] Register with email + password and create the first company'
priority: 2
status: done
tags:
    - auth
    - onboarding
    - register
---

A new visitor signs up with just an email and password. They get a verification email; clicking
the link confirms the account, creates their user and their first company (with them as owner),
and drops them into the dashboard. Registration is intentionally minimal — business/KYC details
come later in onboarding.

## Backend
**Goal:** capture a minimal email+password signup, verify it by email, and on confirmation create
the user, their first company, and the owner membership.

### Accounts schema: users, companies, memberships (+ pending registrations) — _engineer_
- **Goal:** the foundational multi-tenant identity model the whole product sits on.
- **Background:** greenfield. `users` (unique, normalized email; password hash; email-verified
flag), `companies`, and a **many-to-many** membership join (user↔company with a role, e.g.
owner) — one user can belong to many companies and a company to many users. Unverified signups
live as short-lived **pending registrations** (email + password hash + TTL), promoted to real
rows only on verification — keeps the `users` table clean (the "approach A, done tidily" call).

### `POST /auth/register` — _engineer_
- **Goal:** accept an email + password and start email verification without creating the real
account yet.
- **Background:** stores a pending registration (hashed password) and sends the verification
email. Must be **rate-limited per IP and per email**, with outbound email throttled — this is
the spam/abuse surface, so the protection lives here, not in withholding the DB write. A repeat
signup for a still-pending email resends rather than errors; respond generically.

### Email verification link + token — _ai-agent_
- **Goal:** a signed, expiring verification link delivered by email.
- **Background:** single-use and time-boxed; the token references the pending registration only
(no password ever travels in the link).

### `POST /auth/verify` — _engineer_
- **Goal:** confirm the token and bring the account to life.
- **Background:** on a valid token, promote the pending registration into a `users` row, create
the user's **first company** and an **owner** membership joining them, mark the email verified,
and discard the pending record. Expired/used tokens are rejected. The first company may start
with a placeholder name — it is named/filled during KYC/KYB onboarding.

## Frontend
**Goal:** a minimal Laravel signup that ends with the user verified and on the dashboard.

### Register route + controller — _ai-agent_
- **Goal:** render the signup page and submit the credentials.
- **Background:** posts email + password to the Go `/auth/register`; on success shows a "check
your email" state.

### Register form view (email + password) — _engineer_
- **Goal:** the signup UI — just email and password.
- **Background:** deliberately minimal so users reach the dashboard fast; validation errors show
inline.

### Verification landing + redirect to dashboard — _ai-agent_
- **Goal:** handle the click-through from the email and land the user in the app.
- **Background:** hits the Go `/auth/verify`; on success the user is verified and routed to the
dashboard (and on into KYC/KYB onboarding).

## Acceptance criteria  (backend; verified end-to-end against PostgreSQL)
- [x] Submitting email + password creates a `pending_registration` row (hashed password, TTL)
and enqueues a verification email — **no** `"user"` row is created yet.
- [x] Clicking a valid verification link creates the user, their first company, and an owner
membership, sets `email_verified_at`, and deletes the pending record (single transaction).
- [x] An expired or already-used verification link is rejected (`400 invalid_token`); expired
rows are cleaned up on the verify attempt.
- [x] Repeating registration for a still-pending (within cooldown) or already-verified email
responds generically (`202`) — nothing reveals whether the email already exists.
- [x] `/auth/register` is rate-limited per IP (global limiter) and per email (resend cooldown);
verification email delivery goes through the outbox dispatcher.
- [x] Passwords are stored argon2id-hashed and never appear in the verification link (link
carries only the random token; only its sha256 is stored).
- [~] Unverified pending registrations expire after their TTL: **enforced on verify** (expired
tokens rejected + row deleted). A periodic sweeper for never-clicked rows is a small
follow-up (not blocking) — candidate for a scheduled cleanup job.
- [x] _Frontend (this repo):_ after verification the user is routed to login (verify does not
auto-login per contract) and, once signed in, lands on the dashboard.

---

## API contract — frontend handoff (build in parallel)

> Backend-only repo. **Executable source of truth:** Postman →
> `postman/tekomata.postman_collection.json` (folder *API v1 / Auth*).
> **Diagrams & internal design:** `documentation/features/auth-register.md`.
> **Error codes → i18n (EN/ID):** `documentation/error-catalog.md` (+ `.json`).
> Envelope: `{ "data": ... }` on success / `{ "error": { "code", "message", "request_id", "fields"? } }`
> on failure, where `code` is an **i18n key** the FE renders from (not `message`).
>
> ⚠️ **FE REVISION NOTE (error model updated):** `code` is now a stable i18n key;
> responses carry `request_id`; validation `fields[]` now use per-field `code` +
> `params` (e.g. `validation.too_short {"min":8}`); the verify token error is
> `auth.invalid_token` (was `invalid_token`); malformed JSON → `bad_request`.
> Build translations off the catalog.

### `POST /api/v1/auth/register`  — public
Request:
```json
{ "email": "owner@acme.com", "password": "an0therStr0ng!pass" }
```
Validation: `email` valid + normalized lowercase; `password` 8–72 chars.

Response `202 Accepted` (always generic — no email enumeration):
```json
{ "data": { "message": "If that email can be registered, a verification link has been sent." } }
```
Errors: `422 validation_failed` (bad email / short password, see `error.fields`),
`429 rate_limited` (per-IP limit or per-email resend cooldown).
A repeat register for a still-pending or already-verified email **re-sends** and still returns `202`.

Example error `422 Unprocessable Entity`:
```json
{
  "error": {
    "code": "validation_failed",
    "message": "one or more fields are invalid",
    "request_id": "host/abc-000001",
    "fields": [
      { "field": "email", "code": "validation.email", "message": "must be a valid email" },
      { "field": "password", "code": "validation.too_short", "params": { "min": 8 }, "message": "must be at least 8 characters" }
    ]
  }
}
```
Example error `429 Too Many Requests`:
```json
{ "error": { "code": "rate_limited", "message": "too many requests", "request_id": "host/abc-000001" } }
```

### `POST /api/v1/auth/verify`  — public
Request:
```json
{ "token": "base64url-token-from-the-email-link" }
```
Response `200 OK`:
```json
{ "data": { "user_id": "uuid", "company_id": "uuid", "email": "owner@acme.com" } }
```
Errors: `422 validation_failed` (`token` missing),
`400 auth.invalid_token` (unknown / used / expired — generic, no detail leaked),
`400 bad_request` (malformed JSON body).

Example error `400 Bad Request` (invalid/expired token):
```json
{
  "error": {
    "code": "auth.invalid_token",
    "message": "invalid or expired verification token",
    "request_id": "host/abc-000002"
  }
}
```
Example error `422 Unprocessable Entity` (token missing):
```json
{
  "error": {
    "code": "validation_failed",
    "message": "one or more fields are invalid",
    "request_id": "host/abc-000002",
    "fields": [ { "field": "token", "code": "validation.required", "message": "is required" } ]
  }
}
```

> Note: verify does **not** issue a JWT/auto-login (that's the separate login story).
> The email link is `{APP_BASE_URL}/verify?token=...`; the FE landing page POSTs the
> `token` here, then routes to login → dashboard.
