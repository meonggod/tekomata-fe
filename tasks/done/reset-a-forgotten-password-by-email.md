---
name: '[STORY] Reset a forgotten password by email'
priority: 2
status: done
tags:
    - auth
    - password-reset
    - security
---

A user who can't sign in requests a password reset with their email, receives a reset link, and
sets a new password from it. Email only — no OTP — to avoid per-message cost.

## Backend
**Goal:** issue an emailed, single-use reset link and let it set a new password safely.

### `POST /auth/password/forgot` — _engineer_
- **Goal:** start a reset for a given email.
- **Background:** always responds generically whether or not the email exists (no account
enumeration). If it exists, issues a single-use, expiring reset token and emails the link.
**Rate-limited per IP and per email, with outbound email throttled** to prevent abuse.

### `POST /auth/password/reset` — _engineer_
- **Goal:** verify the reset token and set the new password.
- **Background:** the token is single-use and time-boxed; on success it stores the new password
hash, consumes the token, and **invalidates existing refresh tokens/sessions** so any attacker
is logged out. Expired/used tokens are rejected.

## Frontend
**Goal:** a two-step Laravel flow — request a link, then set a new password.

### Forgot-password request route + view — _ai-agent_
- **Goal:** collect the email and trigger the reset.
- **Background:** posts to `/auth/password/forgot`; always shows the same "if that email exists,
we sent a link" confirmation.

### Reset-password route + view — _engineer_
- **Goal:** the set-a-new-password screen reached from the email link.
- **Background:** carries the token from the link, posts the new password to
`/auth/password/reset`, and on success routes to login.

## Acceptance criteria
- [x] Requesting a reset returns the same generic "if that email exists, we sent a link" response
for any input (no account enumeration). _(FE: any accepted submit lands on one generic
"check your email" state; the controller never branches on whether the email exists.)_
- [ ] A registered email receives a single-use, time-boxed reset link.
- [ ] A valid reset link lets the user set a new password, after which the token is consumed.
- [ ] Expired or already-used reset tokens are rejected.
- [ ] A successful reset invalidates existing refresh tokens/sessions.
- [ ] `/auth/password/forgot` is rate-limited per IP and per email, with outbound email throttled.
- [~] After reset the user is routed to login and can sign in with the new password. _(FE: a `200`
from reset routes to `login` with a "please sign in" flash; signing in is the backend's to prove.)_

> **Frontend (this repo) — done.** Two-step Laravel flow built against the API contract:
> - **Forgot-password request route + view** — `GET|POST /forgot-password` (`ForgotPasswordController`,
>   `auth/forgot-password.blade.php`) → `POST /api/v1/auth/password/forgot`. Always shows the same
>   generic "check your email" confirmation; validation/`rate_limited` errors render from the catalog.
> - **Reset-password route + view** — `GET|POST /reset-password` (`ResetPasswordController`,
>   `auth/reset-password.blade.php`). Reads `?token=` into a hidden field, posts it + the new password
>   to `POST /api/v1/auth/password/reset`; on `200` routes to login, on `auth.invalid_reset_token`
>   shows a friendly "request a new link" banner.
> - `AuthApi::forgotPassword()` / `AuthApi::resetPassword()`; a "Forgot password?" link on the login
>   form; EN/ID copy under `messages.forgot.*` / `messages.reset.*` + `errors.auth.invalid_reset_token`.
> Backend-only criteria (token issuance/consumption, session revocation, rate-limit/throttle) left for
> the Go API. Per repo convention, UI stories ship without unit/feature tests — verified via
> `npm run build` + Blade compile + Pint.

---

## API contract — frontend handoff (build in parallel)

> Envelope: `{ "data": ... }` / `{ "error": { "code", "message", "request_id", "fields"? } }`.
> `code` is an i18n key — see `documentation/error-catalog.md`. Full design + Mermaid:
> `documentation/features/auth-password-reset.md`. Executable source of truth: Postman
> (folder "API v1 / Auth" → "Forgot Password" / "Reset Password").

### `POST /api/v1/auth/password/forgot` — public
Starts a reset. **Always** returns the same generic `202` regardless of whether the
email exists (no account enumeration). Rate-limited per IP (route) and throttled per
email (a new link is only issued once the resend cooldown has passed).

**Request**
```json
{ "email": "owner@acme.com" }
```
**Success `202 Accepted`** (identical for known and unknown emails):
```json
{ "data": { "message": "If that email exists, a password reset link has been sent." } }
```
**Errors**

| Status | `code`             | When |
|--------|--------------------|------|
| 422    | `validation_failed`| `email` missing or malformed (see `fields[]`) |
| 429    | `rate_limited`     | per-IP limit exceeded |

```json
{ "error": { "code": "validation_failed", "message": "one or more fields are invalid", "request_id": "host/abc-1",
  "fields": [ { "field": "email", "code": "validation.email", "message": "must be a valid email" } ] } }
```

### `POST /api/v1/auth/password/reset` — public
Verifies the single-use, time-boxed token and sets the new password. On success it
consumes the token and **revokes every existing refresh session** for that user
(any attacker is logged out). Expired/used/unknown tokens are rejected.

**Request**
```json
{ "token": "kQ8b-base64url-from-the-email-link", "password": "an0therStr0ng!pass" }
```
**Success `200 OK`**:
```json
{ "data": { "message": "Your password has been reset. Please sign in." } }
```
**Errors**

| Status | `code`                    | When |
|--------|---------------------------|------|
| 422    | `validation_failed`       | `token` missing, or `password` < 8 / > 72 chars (see `fields[]`) |
| 400    | `auth.invalid_reset_token`| token unknown, already used, or expired |

```json
{ "error": { "code": "auth.invalid_reset_token", "message": "invalid or expired reset token", "request_id": "host/abc-2" } }
```

**Notes for FE**
- After a `200` from reset, route to login; the old session is dead, so the user must sign in fresh.
- The reset link is `${APP_BASE_URL}/reset-password?token=...` — the FE reset view reads `token` and POSTs it with the new password.
- Both endpoints are public (no auth header).
