---
name: '[STORY] Surface request id on frontend errors + user-impact flag for searchable reports'
priority: 3
status: done
tags:
    - backend
    - error-handling
    - frontend
    - observability
    - support
---

When a user hits a backend error in the panel, they see a friendly "something went wrong" state
with a **request id** they can copy and report — and that same id is what we search to land on the
exact failure. On the alerting side, a failure that broke a live user request is flagged
**user-impacting**, so we know a real customer is blocked right now and can triage it ahead of
background noise. One request id ties the user's report, the Slack alert, and the log line
together. Builds on the backend error/panic/Slack alerting story (`86exvtzqa`), which generates
the request id, structured logs, and alerts — this story carries that id to the user's screen and
makes it the search key.

## Backend
**Goal:** return a consistent, safe error envelope carrying the request id, and mark events that
broke a live user request as user-impacting so they surface first — all reusing the request id and
reporter from the alerting story, with no second id scheme.

### Consistent error response envelope — _engineer_
- **Goal:** every API error returns one stable shape the panel can rely on.
- **Background:** a JSON error body with the **request id**, a stable machine error code, and a
safe, user-presentable message — never a stack trace, internal detail, or secret. The request id
is the same correlation id the alerting story (`86exvtzqa`) already puts in logs and alerts, so
it resolves end to end. The error code is for our triage; the request id is what the user quotes.

### User-impact flag derived from request context — _ai-agent_
- **Goal:** distinguish a failure that broke a live user request from a background hiccup, without
anyone setting a flag by hand.
- **Background:** the request context already knows whether it is a **user-initiated request**
(panel/user-JWT or API key) or a **background worker** (queue / assistant orchestrator). The
reporter marks the event `user_facing` accordingly — true when a real user was blocked, false for
background jobs. Derived automatically so it stays honest.

### User-impact flag on the alert + searchable id — _ai-agent_
- **Goal:** make a user-impacting failure obvious in Slack and trivially traceable to logs.
- **Background:** the Slack alert flags `user_facing` events prominently ("a customer is stuck")
so they are triaged ahead of background noise, and shows the request id the user will quote. The
id is the single search key joining the user's report → the alert → the log line; logs are
already structured/searchable by it (from `86exvtzqa`).

## Frontend
**Goal:** turn a backend error into a calm, branded state that hands the user the request id and an
easy way to report it — never a raw stack or 500 page.

### Global error handling + branded error state — _engineer_
- **Goal:** catch API error responses and the panel's own unhandled exceptions and render one
consistent error screen.
- **Background:** reads the error envelope's request id and shows a "something went wrong" state
with the id and a **copy action**, plus a short "share this code when you contact us" line. Shows
the id for unexpected failures (the ones we'd alert on); ordinary validation errors stay as clean
inline messages with no scary id.

### "Report this" affordance — _ai-agent_
- **Goal:** let the user send the error to us with the id already attached.
- **Background:** a simple support link / prefilled mailto carrying the request id (and error code),
so the report arrives with the id, not a vague "it broke". A real support/ticketing integration
is a later story.

## Acceptance criteria

**Backend (this repo):**
- [x] Every API error returns a consistent envelope containing the request id, a stable error code,
and a safe message — no stack trace, internal detail, or secret. _existing `response.ErrorBody`;
asserted by `middleware.TestRecoverer_...` (500 body carries `code`+`request_id`, no stack/panic leak)._
- [x] The request id in the envelope is the same id used in logs and Slack alerts, so a
user-reported id resolves to the exact log line and alert. _all read chi `RequestID`._
- [x] Events are marked `user_facing` automatically from request context — true when a live user
request failed, false for background-worker / queue failures — with no manual flag.
_`domain/alert.Source` → `Alert.UserFacing`; tested (http=true, worker=false)._
- [x] User-impacting failures are flagged prominently in the Slack alert and carry the request id,
so they can be triaged ahead of background noise and searched directly. _⚠️ banner in `resource/slack`._
- [x] The error envelope and error page never expose secrets or PII — only the request id, error
code, and a safe message. _generic internal message + `Redact`; tested._
- [x] Request ids and error visibility are tenant-scoped — one company can never search into or see
another company's errors. _per-request `*RequestMeta` pointer._

**Backend note on the "4xx" criterion:** the envelope keeps `request_id` on **all** responses
(4xx included — it is not a secret); the backend satisfies "clean validation errors" by simply
**not flagging 4xx as user-impacting and not sending them to Slack**. Whether to *display* the id is
a frontend policy (below).

**Frontend (Laravel repo — implemented here):**
- [x] On an unexpected backend failure, the panel shows a branded error state with the request id
and a copy action — never a raw stack trace or default 500 page. _FE: a dismissible "something went
wrong" modal pops up over the page on 5xx during form posts; a GET with no page to overlay falls back
to the branded full-page state. Both carry the request id + a copy button._
- [x] Ordinary validation errors (4xx) remain clean inline messages without a request id / support
code. _FE: only `ApiUnavailableException` (5xx / unreachable, after retries) triggers the modal;
422/401/429 stay inline as before — the request id is never surfaced for them._
- [~] The user has a way to report the error (support link / prefilled mailto) with the request id
already attached. _Adjusted per product call: no mailto/report link — the help desk is already
notified backend-side (alerting story `86exvtzqa`). The FE simply surfaces the request id (with copy)
for the user to quote, which is the single code that resolves to our logs + Slack alert._

> **Frontend (this repo) — done.** Global 5xx error surface built against the error envelope:
> - `TekomataApiException::requestId()` reads `error.request_id` from the envelope (null when the API
>   was unreachable and returned no body).
> - **Branded error state + modal** — `Controller::apiErrorModal()` redirects `back()` with an
>   `api_error` flash on 5xx; `components/error-modal.blade.php` (wired into the app layout) pops a
>   dismissible dialog with the message, the request id + copy, and a "quote this code" hint. Backdrop /
>   Esc / Dismiss all close it (`resources/js/app.js`) — it notifies, it doesn't block.
> - Login / Register / Forgot / Reset / Company-switch each route 5xx to the modal (a leading
>   `catch (ApiUnavailableException)`), keeping 4xx inline. Verify (GET) lets 5xx bubble so a bad
>   link and an outage no longer look the same.
> - Full-page fallback (`errors/api-unavailable.blade.php`) + the JSON 503 now also carry the request id.
> - EN/ID copy under `messages.errors.*` (modal title/body, ref label/hint, copy/copied/dismiss).
> Backend criteria (envelope, `user_facing` flag, Slack alert, tenant scoping) were satisfied in the Go
> repo. Per repo convention, UI ships without unit/feature tests — verified via `npm run build`, Blade
> compile, a render smoke-test of the modal + `requestId()`, and Pint.

---

## API contract — frontend handoff (build in parallel)

This story adds **no new endpoint**. It standardises the **error envelope** every
existing and future endpoint already returns, so the panel can build one global error
handler against a stable shape. Executable source of truth: the Postman collection
(every request's saved error example uses this envelope).

### The error envelope (every API error, all endpoints)

```
{ "error": { "code": <string>, "message": <string>, "request_id": <string>, "fields"?: [...] } }
```

| Field | Meaning | FE usage |
|-------|---------|----------|
| `code` | stable machine i18n key (e.g. `validation_failed`, `auth.invalid_credentials`, `internal_error`) | map to a localized message; drives FE branching |
| `message` | safe English fallback (never a stack/secret/internal detail) | debug only — **do not display** to end users; localise via `code` |
| `request_id` | correlation id — **same** id in our logs + Slack alert | show + offer "copy" / "report" **for unexpected (5xx) failures**; may be ignored for ordinary 4xx |
| `fields[]` | per-field validation failures `{field, code, params?}` (only on 422) | render inline next to each form field |

**`request_id` is present on EVERY error body, 4xx and 5xx alike** — it is not a secret.
Whether to surface it is a **frontend policy**: show the branded "something went wrong"
state with the id + copy/report action for **unexpected** failures (the 5xx we alert on),
and keep ordinary **validation** errors as clean inline messages (the id can be ignored).
There is no separate "support code" — the `request_id` is the single code the user quotes,
and it resolves end-to-end to the exact log line and Slack alert.

> `user_facing` is a **backend/alerting-internal** signal (it makes a stuck-customer
> failure jump the queue in Slack). It is **not** part of the client envelope — the FE
> does not read it; it only ever sees the `error` object above.

### Example — validation error (HTTP 422)

```json
{
  "error": {
    "code": "validation_failed",
    "message": "one or more fields are invalid",
    "request_id": "c3f8a1e2-8b1d-4a77-9b1d-2f3a4b5c6d7e",
    "fields": [
      { "field": "email", "code": "validation.required" }
    ]
  }
}
```
FE: render `email` field error inline. No "something went wrong" screen, id not surfaced.

### Example — unexpected server error (HTTP 500)

```json
{
  "error": {
    "code": "internal_error",
    "message": "an internal error occurred",
    "request_id": "c3f8a1e2-8b1d-4a77-9b1d-2f3a4b5c6d7e"
  }
}
```
FE: show the branded error state with `request_id` + a copy/"report this" action. The
**same** `request_id` is in our structured logs and the Slack alert, so support can land
on the exact failure. `message` is generic by design — no stack, internal detail, or secret.

### Example — auth rejection (HTTP 401)

```json
{
  "error": {
    "code": "auth.invalid_credentials",
    "message": "invalid email or password",
    "request_id": "c3f8a1e2-8b1d-4a77-9b1d-2f3a4b5c6d7e"
  }
}
```
FE: inline "invalid email or password". A 4xx — not alerted, id not surfaced.

Full design + diagrams: `documentation/features/observability-alerting.md`.
