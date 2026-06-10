---
name: '[STORY] Company settings'
priority: 3
status: done
tags:
    - company
    - currency
    - settings
---

The company owner can view and update their company's operational settings from a dedicated
page in the control panel: identity (name, logo, country, timezone), assistant behavior
(persona, language, business hours), billing currency, notification emails, and WhatsApp
numbers. Unlike KYC/KYB, these are always editable after onboarding.

## Backend

**Goal:** persist and expose company-level settings, with validation scoped to the active company.

### Company settings schema — _engineer_
- **Goal:** store the configurable settings fields against the company row (or a companion
`company_settings` table if the `companies` table is already wide), and two priority-ordered
contact tables for emails and WhatsApp numbers.
- **Background:**
- **`company_settings`** (or columns on `companies`): `currency_id` (FK to `currencies`,
must be platform-active), `country_id` (FK to `countries`), `timezone` (IANA string, e.g.
`Asia/Jakarta`), `display_name` (editable post-KYB), `logo_file_id` (FK to private file
storage), `assistant_persona_name` (the bot's display name in WhatsApp replies),
`reply_language` (enum: `id`, `en`, `auto`), `business_hours` (JSON — see below),
`out_of_hours_message` (text sent outside active hours; null = always active).
- **`company_emails (id, company_id, email, priority)`** — multiple notification/billing
emails per company; `priority` is an integer where `0` = p0 (default, used for billing
alerts and critical notices). Lower number = higher priority.
- **`company_whatsapp_numbers (id, company_id, number, priority)`** — multiple WhatsApp
numbers per company; `priority 0` = primary (the number used for inbound webhook tenant
identification). Lower number = higher priority.
- **`business_hours` JSON shape:** a day-keyed object where each day holds an array of
`{ open: "HH:MM", close: "HH:MM" }` slots, supporting non-contiguous ranges (e.g.
`"monday": [{ "open": "09:00", "close": "12:00" }, { "open": "13:00", "close": "21:00" }]`).
A day with an empty array means closed that day. Omitting a day means closed.

### `GET /settings` — _ai-agent_
- **Goal:** return the active company's current settings including all emails and WhatsApp numbers.
- **Background:** scoped to the token's active company. Returns the settings object with
resolved currency symbol, country name, timezone, plus `emails` and `whatsapp_numbers` as
arrays sorted by priority ascending (p0 first) — so the UI can display everything in one call.

### `PATCH /settings` — _engineer_
- **Goal:** accept partial updates to the flat company settings fields and persist them.
- **Background:** validates `currency_id` is platform-active, `country_id` exists, `timezone`
is a known IANA zone, `reply_language` is `id`/`en`/`auto`, `business_hours` JSON structure
(each slot's open is before close, no overlapping slots on the same day), and `logo_file_id`
belongs to the active company. Only fields sent are updated (PATCH semantics). Does not
manage the emails/whatsapp_numbers collections — those have their own endpoints.

### Email contact endpoints — _engineer_
- **Goal:** manage the list of company notification emails with priorities.
- **Background:**
- `POST /settings/emails` — add an email; priority defaults to current lowest + 1 if not
specified.
- `DELETE /settings/emails/:id` — rejected if it is the only remaining email; if it is the
p0, the entry with the next lowest priority is automatically promoted to p0.
- `PATCH /settings/emails/:id` — update the email address or priority; promoting an entry to
p0 demotes the previous p0 to p1 automatically (no two entries share the same priority).

### WhatsApp number endpoints — _engineer_
- **Goal:** manage the list of company WhatsApp numbers with priorities.
- **Background:**
- `POST /settings/whatsapp-numbers` — add a number; validates E.164 format.
- `DELETE /settings/whatsapp-numbers/:id` — rejected if it is the only remaining number; if
it is the p0, the next lowest-priority number is automatically promoted to p0 (and becomes
the new inbound webhook identifier).
- `PATCH /settings/whatsapp-numbers/:id` — update the number or priority; promoting to p0
demotes the previous p0 automatically.

## Frontend

**Goal:** a settings page where the owner can see and update their company's configuration.

### Settings page — _engineer_
- **Goal:** render the current settings and handle submissions per section.
- **Background:** reads from `GET /settings` on load. Sections: **Company** (display name,
logo, country, timezone), **Assistant** (persona name, reply language, business hours,
out-of-hours message), **Billing** (active currency, notification emails), **WhatsApp**
(business numbers). Each section saves independently. Shows inline validation errors and a
success confirmation on save.

### Currency + Country + Timezone selectors — _ai-agent_
- **Goal:** let the owner pick from valid options only.
- **Background:** currency list from platform-enabled currencies (currency-catalog story);
country list from platform-active countries; timezone from a standard IANA zone list.
All three are dropdowns with search/filter.

### Logo upload — _ai-agent_
- **Goal:** let the owner upload and replace the company logo.
- **Background:** uses the shared private file storage upload flow (storage story). On
successful upload, the returned file id is saved via `PATCH /settings`. Current logo is
previewed; uploading a new one replaces it.

### Multi-entry email manager — _ai-agent_
- **Goal:** let the owner manage multiple notification emails with visible priority tiers.
- **Background:** displays entries sorted by priority (p0 first, labeled "Primary"). The owner
can add entries, remove any entry (the last one is disabled for removal), and promote any
entry to p0 (which demotes the current p0 automatically). Each entry shows its priority
badge. Uses the email contact endpoints.

### Multi-entry WhatsApp number manager — _ai-agent_
- **Goal:** let the owner manage multiple WhatsApp numbers with visible priority tiers.
- **Background:** same pattern as the email manager — sorted list, p0 labeled "Primary
(active for assistant)". The owner can add, remove any entry (last one disabled), and
promote any to p0 (auto-demotes current p0). Promoting a number to p0 makes it the inbound
webhook identifier. Uses the WhatsApp number endpoints.

### Business hours configurator — _ai-agent_
- **Goal:** let the owner define active time slots per day and an out-of-hours message.
- **Background:** per-day toggles (Mon–Sun); each enabled day shows a list of time-slot rows
(`open` / `close` HH:MM pair) with add/remove controls so the owner can define non-contiguous
hours (e.g. 09:00–12:00 and 13:00–21:00 on the same day). Validates open < close and no
overlapping slots client-side before submit. A global toggle to disable business hours
entirely (always active). Out-of-hours message textarea shown only when hours are enabled.

## Acceptance criteria

_Backend-observable criteria only (per `tasks/CLAUDE.md`). Verified end-to-end against a live DB
(login → GET/PATCH /settings → email & whatsapp add/promote/delete + validation guards)._

- [x] `GET /settings` returns all current values in one call: display name, logo (file id),
country, timezone, billing currency, assistant persona name, reply language, business hours,
out-of-hours message, all notification emails + WhatsApp numbers (sorted by priority asc).
- [x] The owner can add multiple notification emails and WhatsApp numbers, each with an assigned
priority; `priority: 0` is the primary/default (FE labels it).
- [x] Promoting an entry to p0 automatically demotes the previous p0 — no two entries share
the same priority level (server renumbers the contiguous `0..n-1` list in one tx).
- [x] The p0 WhatsApp number is the deterministic primary intended for inbound webhook tenant
identification. _(The webhook consumer itself is a future story; the ordering guarantee is in place.)_
- [x] Deleting the only remaining email or WhatsApp number is rejected (`409`); deleting a p0 entry
when others exist auto-promotes the next lowest-priority entry to p0.
- [x] A day can have multiple non-contiguous slots (e.g. 09:00–12:00 and 13:00–21:00); overlapping
slots on the same day are rejected (`422 validation.overlap`).
- [x] Each slot validates open < close (`422 validation.time_range`).
- [x] `reply_language` only accepts `id`, `en`, or `auto` (`422 validation.invalid_value`).
- [x] Selecting a country is limited to platform-active countries (`422 validation.invalid_country`).
Currency is **read-only** here (changed via `/company/currencies`, which already enforces
platform-active) — per the approved design.
- [x] All changes are scoped to the active company (every query filters by the Principal's `company_id`).
- [x] A company with no settings saved yet gets a default state from `GET /settings` (no 404) and can
fill it in from scratch via `PATCH` (upsert).
- [x] Tests pass: `go vet ./...` + `go test ./...` green (the `golangci-lint` binary is incompatible
with Go 1.25 in this environment, so `make lint` can't run here — pre-existing, unrelated to this change).
- [~] Out-of-hours message **required when business hours are enabled** — FE/business rule; the backend
stores `out_of_hours_message` freely (it does not enforce conditional-required across fields). Deferred to FE.
- [~] Logo upload stores the file via the private storage layer + preview — depends on the **storage
story** (not built). `logo_file_id` is accepted/persisted/returned now; FK + ownership validation land then.

---

> **Frontend (this repo) — done.**
> - `CompanySettingsApi` — GET /settings, PATCH /settings, full email + WhatsApp CRUD.
> - `CompanySettingsController` — show, updateCompany, updateAssistant, addEmail, deleteEmail, promoteEmail, addWhatsapp, deleteWhatsapp, promoteWhatsapp.
> - `resources/views/settings/index.blade.php` — 4 independent save sections: Company (display name, country, timezone), Assistant (persona name, reply language, business hours, OOH message), Billing (read-only currency + email manager), WhatsApp (number manager with promote/delete).
> - Business hours configurator: vanilla JS add/remove slot rows, show/hide per mode + day toggle.
> - Sidebar updated with Settings nav item.
> - Logo upload deferred (storage story dependency). Client-side OOH required validation deferred (already marked [~]).

---

## API contract — frontend handoff (build in parallel)

All routes under `/api/v1`, **Bearer JWT**, tenant-scoped to the active company (from the token
Principal — never the body). Full design + Mermaid: `documentation/features/company-settings.md`.
Executable source of truth: `postman/tekomata.postman_collection.json` → folder "Company Settings".

**Decisions baked into this contract** (diverge from the story where it didn't fit the codebase):
- Catalogs are keyed by **code**, not id (`country_code` = ISO-3166-1 alpha-2, currency = ISO-4217).
- **Billing currency = the company's existing default enabled currency** — surfaced read-only here;
  changed via the existing `PUT /api/v1/company/currencies/{code}/default`. `PATCH /settings` does
  not touch currency.
- **Country** reuses the canonical `company.country_code`; `PATCH /settings {country_code}` updates it.
- **`display_name`** is separate from `company.name` (which KYB overwrites); falls back to it when unset.
- **Logo** `logo_file_id` is a nullable `uuid`, accepted as-is — no FK/ownership check until the
  storage story lands (contract is already final).
- **Priorities** are a server-managed contiguous `0..n-1` list, `0` = primary. Send the target
  `priority`; the server reorders siblings. Deleting the last entry → `409`.

### `GET /settings` → `200`
Returns the whole settings page in one call. `emails`/`whatsapp_numbers` sorted by `priority` asc.
```json
{ "data": {
  "display_name": "Reynaldi's Company",
  "country": { "code": "ID", "name": "Indonesia" },
  "timezone": "Asia/Jakarta",
  "logo_file_id": null,
  "billing_currency": { "code": "IDR", "name": "Indonesian Rupiah", "symbol": "Rp", "decimal_places": 0 },
  "assistant_persona_name": "Teko",
  "reply_language": "auto",
  "business_hours": { "monday": [ { "open": "09:00", "close": "12:00" }, { "open": "13:00", "close": "21:00" } ] },
  "out_of_hours_message": "We're currently closed.",
  "emails": [
    { "id": "e1000000-0000-4000-8000-000000000001", "email": "billing@acme.co", "priority": 0 },
    { "id": "e1000000-0000-4000-8000-000000000002", "email": "ops@acme.co", "priority": 1 } ],
  "whatsapp_numbers": [ { "id": "wa000000-0000-4000-8000-000000000001", "number": "+6281234567890", "priority": 0 } ],
  "updated_at": 1781000000000
} }
```
No settings saved yet → defaults: `display_name` from `company.name`, `business_hours: null`,
`emails: []`, `whatsapp_numbers: []`, `logo_file_id: null`, `billing_currency: null` if no default.
**Errors:** `401 unauthorized`.

### `PATCH /settings` → `200` (full settings view)
Partial upsert; only keys present are changed. All fields optional:
```json
{ "display_name": "Acme Corp", "country_code": "ID", "timezone": "Asia/Jakarta",
  "logo_file_id": "f1000000-0000-4000-8000-000000000001", "assistant_persona_name": "Teko",
  "reply_language": "id",
  "business_hours": { "monday": [ { "open": "09:00", "close": "17:00" } ] },
  "out_of_hours_message": "Closed — back at 9am." }
```
- `reply_language` ∈ `id` | `en` | `auto`.
- `business_hours`: day-keyed (`monday`..`sunday`), each an array of `HH:MM` `{open,close}` slots;
  `[]` / omitted day = closed, `null` (whole field) = always active; each slot `open < close`, no
  same-day overlap.
- `logo_file_id`: uuid or `null` (clear).
**Errors (example body):**
```json
{ "error": { "code": "validation_failed", "message": "One or more fields are invalid.",
  "request_id": "c0ffee…",
  "fields": [
    { "field": "timezone", "code": "validation.invalid_timezone", "message": "Unknown time zone." },
    { "field": "business_hours.monday[0]", "code": "validation.time_range", "message": "open must be before close." } ] } }
```
`422 validation_failed` with `fields[].code = validation.invalid_country` (country not platform-active) · `401 unauthorized`.

### `POST /settings/emails` → `201`
Req `{ "email": "ops@acme.co", "priority": 1 }` (`priority` optional → appended).
Res `{ "data": { "id": "e1…002", "email": "ops@acme.co", "priority": 1 } }`.
**Errors:** `422 validation_failed` (`validation.email`) · `409 settings.email_taken`.

### `PATCH /settings/emails/{id}` → `200`
Req `{ "email": "billing@acme.co", "priority": 0 }` (both optional; `priority:0` promotes, auto-demotes old p0).
Res `{ "data": { "id": "e1…001", "email": "billing@acme.co", "priority": 0 } }`.
**Errors:** `404 settings.email_not_found` · `409 settings.email_taken` · `422 validation_failed`.

### `DELETE /settings/emails/{id}` → `204`
Deleting the p0 auto-promotes the next.
**Errors:** `404 settings.email_not_found` · `409 settings.last_email` (only remaining).

### `POST /settings/whatsapp-numbers` → `201`
Req `{ "number": "+6281234567890", "priority": 0 }` (`priority` optional; **E.164** validated).
Res `{ "data": { "id": "wa…001", "number": "+6281234567890", "priority": 0 } }`.
**Errors:** `422 validation_failed` (`validation.invalid_phone`) · `409 settings.whatsapp_taken`.

### `PATCH /settings/whatsapp-numbers/{id}` → `200`
Req `{ "number": "+6281100002222", "priority": 0 }` (both optional; `priority:0` makes it the inbound-webhook id).
Res `{ "data": { "id": "wa…001", "number": "+6281100002222", "priority": 0 } }`.
**Errors:** `404 settings.whatsapp_not_found` · `409 settings.whatsapp_taken` · `422 validation_failed`.

### `DELETE /settings/whatsapp-numbers/{id}` → `204`
**Errors:** `404 settings.whatsapp_not_found` · `409 settings.last_whatsapp_number`.

### New error codes (added to `pkg/apperr` + `documentation/error-catalog.md`)
`settings.email_not_found` (404) · `settings.email_taken` (409) · `settings.last_email` (409) ·
`settings.whatsapp_not_found` (404) · `settings.whatsapp_taken` (409) · `settings.last_whatsapp_number` (409).
Field codes: `validation.invalid_timezone`, `validation.invalid_phone`, `validation.time_range`,
`validation.overlap` (`validation.invalid_country`/`validation.email`/`validation.required` already exist).
