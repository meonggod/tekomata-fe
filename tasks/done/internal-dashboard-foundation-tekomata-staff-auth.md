---
name: '[STORY] Internal dashboard foundation — tekomata-staff auth & /internal/* shell'
priority: 2
status: done
tags:
    - internal-dashboard
    - admin
    - auth
---

tekomata staff get a dedicated console at **`/internal/*`**. This story builds the spine the whole
console hangs off: a **tekomata-staff identity** separate from tenant auth, the guard every config
endpoint sits behind, a shared audit trail for config writes, and the dashboard shell + staff login.
Today a dozen shipped stories say their settings are "managed in the tekomata-internal dashboard, no
deploy needed" — but the dashboard, and the staff principal those admin endpoints assume, were never
built. The actual config panels ship as follow-on stories (`internal-dashboard-billing-config`,
`internal-dashboard-ai-config`, `internal-dashboard-cs-review`) that mount behind this guard.

## Backend
**Goal:** introduce a tekomata-staff principal wholly separate from tenant auth, expose the
`/internal/*` guard the panel stories mount behind, and give every config write a shared audit trail.

### `tekomata_staff` + staff roles migration — _ai-agent_
- **Goal:** model platform operators as their own accounts, independent of the company user↔company model.
- **Background:** staff are **not** tenant users — they don't belong to a company and must never be
resolved through the existing many-to-many membership/active-company auth (see *Accounts, tenancy &
auth*). A staff row has email + password and a **role** (e.g. `superadmin` vs limited `ops`) so
money-moving changes can be restricted from view-only operators.

### Staff auth + `/internal/*` guard middleware — _engineer_
- **Goal:** let staff log in and authorize only staff against the internal endpoint surface.
- **Background:** email + password login reusing the existing email-link verification/reset patterns
(no OTP). The guard is a **distinct middleware from the tenant JWT middleware** — it exposes a staff
id + role, never an active-company, and a normal company token must be rejected at `/internal/*`.
This is the seam every panel story routes its config endpoints behind.

### Config-change audit log — _ai-agent_
- **Goal:** make every config write traceable to a staff member, for all panels.
- **Background:** shared infrastructure — record staff id, the entity changed, and before/after on
each config write. The panel stories' endpoints all write through it. These values move money (base
rates, feature prices, referral %), so an unattributed change is unacceptable.

## Frontend
**Goal:** the `/internal/*` Laravel console shell — staff login and a role-gated frame the panels plug into.

### Internal dashboard shell + staff login (Blade) — _engineer_
- **Goal:** the `/internal` layout, navigation frame, and staff login/logout.
- **Background:** a surface **separate from the company control panel** — different audience (tekomata
operators, not owners), different auth. Navigation is **role-gated** so limited operators don't see
controls they can't use; the panel stories add their nav entries into this frame. Calls the Go
staff-auth endpoints.

## Acceptance criteria (backend)
- [x] tekomata staff log in to `/internal` with an account that has no company membership; a company
user's token is rejected everywhere under `/internal/*`. *(verified: tenant JWT → 401 at `/internal/me`)*
- [x] The `/internal/*` guard is a distinct middleware from tenant auth (`RequireStaff`) — it exposes a
staff id + role (`StaffPrincipal`) and never an active-company.
- [x] Staff roles exist and gate access: `ops` is view-only; only `superadmin` (`RequireStaffMutate`) can
mutate. *(verified: superadmin write → 201; role model unit-tested)*
- [x] A shared audit log (`audit_log`) records who changed what (staff id + email, action, entity, status,
submitted body) for every mutating `/internal/*` request. *(verified: writes recorded incl. status)*
- [x] Staff login/logout/refresh + invite/set-password/forgot work; first superadmin bootstrapped from config.
- [x] *(Frontend — Laravel)* The console shell renders with role-gated navigation. Built in the FE repo.

## API contract — frontend handoff (build in parallel)
See **`documentation/features/internal-dashboard-foundation.md`** for the full contract (every endpoint
with example success + error bodies) and Mermaid diagrams. Postman: **Internal Dashboard** folder in
`postman/tekomata.postman_collection.json`. Summary: public `POST /api/v1/internal/auth/{login,refresh,
logout,set-password,password/forgot}`; staff-guarded reads `GET /internal/{me,staff,audit-log,fx/rates,
subscription-plans,promo-codes,feature-prices,knowledge-entries,cs-questions}`; superadmin writes
`POST /internal/staff` + the config CRUD. apperr: `staff.{invalid_credentials,invalid_refresh_token,
invalid_token,not_found,email_taken,forbidden}`.

**Status:** done
