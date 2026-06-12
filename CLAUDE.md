# CLAUDE.md — tekomata frontend

> Read `company.md` for the product/business context. This file is the engineering guide for
> **this repo only**: the Laravel **web control panel**.

## What this repo is
Laravel + Blade + Tailwind. **UI layer only** — it renders pages and **calls the Go API** for
all data and auth. No business logic or product DB here; the Go API is the single backend and
source of truth.

This repo also ships the **embeddable web-chat widget** (`public/js/widget.js`) — a standalone
vanilla JS file (no Vite, no build step) that any business pastes onto their website via a single
`<script>` tag. It talks directly to the Go API's public webhook, not through Laravel.

## Golden rules
1. **No local DB for product/tenant data** — everything goes through `app/Services/Tekomata`.
   Laravel's DB is only for framework needs (session, cache, queue).
2. **Auth lives in the Go API.** Login returns a JWT access/refresh pair; we keep it
   **server-side in the session** (`TokenStore`), never in the browser. On expiry the
   `auth.api` middleware refreshes once, else redirects to login.
3. **Tenant scope is implicit** — active company rides inside the JWT; never trust it from the request.
4. **Small, single-purpose units** — thin controllers, API calls in the service layer, no UI strings hard-coded.
5. **Production posture** — every API call has a timeout + bounded retry; failures degrade to a
   friendly page (`ApiUnavailableException` → 503 view), never a stack trace.

## Key locations
- `app/Services/Tekomata/` — Go API client (`TekomataClient`: timeouts, retries, status→typed
  exception), `TokenStore` (session JWT; `userId()` decodes the access-token JWT `sub` claim —
  `/auth/me` is 404 and `user()` is never populated, so use `userId()` for the signed-in user id),
  `AuthApi`, `InboxApi`, `TeamChatApi`, `WalletApi`, `Exceptions/`.
- `app/Http/Middleware/` — `EnsureAuthenticated` (`auth.api` alias), `EnsureOnboarded`
  (`ensure.onboarded`), `EnsureInternalStaff` (`internal.staff`), `SetLocale`.
- `app/Http/Controllers/` — thin controllers: `CompanySettingsController` (settings page),
  `InboxController` (agent inbox + cursor-paginated thread), `TeamChatController` (internal team
  chat), `WalletController` (prepaid IDR wallet).
- `config/services.php` → `tekomata` — API base URL/timeouts/retries (from `.env`).
- `resources/views/` — Blade; layout at `components/layouts/app.blade.php`.
- `resources/views/settings/` — company settings (company identity, assistant, billing,
  WhatsApp numbers, web-chat widget embed code).
- `resources/views/inbox/` — omnichannel agent inbox (conversations list + thread).
- `resources/views/team/` — internal team chat.
- `resources/views/wallet/` — prepaid IDR wallet (spendable + reward balances, top-up/convert/
  withdraw, bucket-tagged transaction history).
- `public/js/widget.js` — **embeddable web-chat widget** (standalone IIFE, no build step).
  Served at a stable URL for external `<script>` embedding. Do **not** move this into Vite.
- `resources/js/app.js` — panel JS: copy-to-clipboard, country combobox, business-hours
  configurator, inbox split-pane, team-chat split-pane, wallet (all progressive enhancement, no
  framework). The inbox/team thread logic is the most involved — see **Messaging UI** below.
- `tasks/` — `[STORY]` docs from ClickUp (see `tasks/README.md`).

### URL structure (two audiences)
- `/` — public marketing + auth (`/login`, `/register`, `/verify`, `/forgot-password`, …).
- **`/app/*`** — the tenant control panel (dashboard, products, inbox, settings, …). The whole
  authenticated group is wrapped in `Route::prefix('app')`; **route names are unchanged**
  (`dashboard`, `products.index`, …), so always link with `route()`/`redirect()->route()` — never a
  hardcoded path. JS that needs a panel URL reads it from a `data-*` attribute rendered by Blade
  (`data-index-url`, `data-*-url-template`), so the prefix lives in one place. `{id}` placeholders in
  those templates are replaced client-side.
- **`/internal/*`** — tekomata-staff ops area (separate audience). Guard: `auth.api` + `internal.staff`
  (`EnsureInternalStaff`, deny-by-default; staff via API claim or `TEKOMATA_INTERNAL_EMAILS` allowlist).
  Not gated by onboarding. Own minimal layout `components/layouts/internal.blade.php` (no tenant sidebar).

## Localization (ID default + EN) — where to edit
No hard-coded strings; use `__('messages.<key>')`. To fix wording, edit the value in **both**
(keep keys identical; missing keys fall back to EN):
- `lang/id/messages.php` · `lang/en/messages.php`
- Languages offered + labels: `config/locales.php` · default: `APP_LOCALE` (`id`), fallback `APP_FALLBACK_LOCALE` (`en`)
- Applied per request: `app/Http/Middleware/SetLocale.php` · switch route `/locale/{code}` · UI `components/lang-switcher.blade.php`
- Run `php artisan view:clear` if a change doesn't show.

## Environment (`.env`)
```
TEKOMATA_API_URL, TEKOMATA_API_TIMEOUT, TEKOMATA_API_CONNECT_TIMEOUT,
TEKOMATA_API_RETRIES, TEKOMATA_API_RETRY_SLEEP_MS
APP_LOCALE=id   SESSION_DRIVER=database   # shared driver so JWTs survive scaling
```

## Work flow (ClickUp → tasks/ → code)
Export a refined `[STORY]` into `tasks/STORY-*.md`, implement **only the `## Frontend` units**,
satisfy its `## Acceptance criteria`. The doc says *what/why*; the *how* is decided in the repo.

**Board movement is automatic — see `tasks/CLAUDE.md` and do it without being asked:**
- Start a story → move its file `todo/ → doing/`.
- Frontend units done + frontend criteria ticked → move `doing/ → done/`, then immediately pick up
  the next `todo/` story. If `todo/` is empty, stop and report the board is clear.
- Don't pause to ask "should I move it?" — if the frontend work is finished, move it. Tick **only
  the frontend-owned** acceptance boxes; leave backend-only criteria as the backend left them.

## Dev commands
```
composer install && npm install && cp .env.example .env && php artisan key:generate
npm run dev            # Vite watch          npm run build      # prod assets
php artisan serve      # or Laragon host     php artisan test   # tests
./vendor/bin/pint      # format (PSR-12)
```

## Conventions
PHP 8.3, PSR-12 (run Pint). Thin controllers — no inline HTTP calls; go through the service
layer. Validate every request. Never echo upstream errors raw — log detail, show a friendly
message. Blade components for anything reused. Secrets only via `config()`/`.env`.

## Tooling constraints
**Never use Python, Node scripts, or any language besides PHP/Bash** for file manipulation,
text replacement, or automation in this repo. Use `php artisan tinker`, `sed`, `php -r`, or
Bash one-liners when a quick transform is needed.

## Smart-quote / curly-quote hazard in `lang/` files
The `lang/*.php` files use **single-quoted PHP strings**. Editors and copy-paste sometimes
silently convert ASCII apostrophes (`'` U+0027) into Unicode smart quotes (`'` U+2018 / `'` U+2019).
PHP only recognises ASCII `'` as a string delimiter, so a stray smart quote **breaks parsing**.

Rules for `lang/` edits:
1. If a string value contains an apostrophe (e.g. *company's*), wrap the value in **double quotes**
   (`"Company's profile"`) instead of escaping.
2. After editing any `lang/` file, **always run `php -l lang/en/messages.php`** (and the `id`
   variant) to catch syntax errors before moving on.
3. Never bulk-replace quote characters without verifying byte values — the Edit tool can
   silently introduce smart quotes when the old text already contained them.

## Page width / layout standard
The standard page width + padding lives **once** on `<main>` in
`resources/views/components/layouts/app.blade.php` (`mx-auto w-full max-w-5xl px-4 py-6 …`). Every
normal (scrolling) page inherits it — **do not** wrap a page in its own `mx-auto max-w-*` outer
container; just emit content and it lands at the standard width. Pages that want a narrower column
(forms, detail views) add an **inner** `mx-auto max-w-xl` wrapper only — never re-declare the outer
page width. Full-height split-pane pages (inbox/team) pass `:full-height="true"`, which opts out of
the width cap so they can go full-bleed. Sidebar sections that bundle sibling views switch with
segmented tabs (`x-products-tabs`, `x-messages-tabs`).

## Messaging UI (inbox + team chat) — non-obvious rules
Both threads live in `resources/js/app.js` (`initInbox`, `initTeamChat`) as a split-pane SPA over
server-rendered partials (`inbox/partials/thread.blade.php`, `team/partials/thread.blade.php`). They
look similar but **attribute "who sent this" differently** — and that difference cost real debugging,
so respect it:

- **Inbox aligns by `direction`.** The API enum is **`in | out | internal`** (NOT
  `inbound/outbound`) — normalise `in→inbound` (left/gray), `out→outbound` (right/indigo),
  `internal`→centered note, in **both** the Blade partial and `createMessageEl`. The customer inbox
  filters out `kind: internal` conversations (`InboxController::externalOnly`) and redirects a direct
  visit to an internal thread to `/app/team`.
- **Team chat aligns by `participant_id`.** Team messages carry **only `participant_id`** — no
  `author_user_id`/`author_name` — and there is no participant→user map endpoint. So the FE learns
  *its own* participant id from the response to messages **it sends**, stored in a `Set` in
  `localStorage` keyed per user (`tekomata.team.my_participants:<userId>`). A message is "mine" iff
  its participant_id is in that set; until you've sent once in a thread, your own past messages sit
  left. See memory `team-chat-author-attribution-gap`.
- **The SSE echo of your own message has fewer fields** than the POST response (team echo lacks
  `participant_id`). Merge same-id messages so the richer copy wins; during a send, buffer SSE echoes
  until the response lands (`pendingSends`/`sseBuffer`) to avoid a left→right flash.
- **Optimistic send** (both threads): clear the composer and render the message on the right
  **immediately** (`__pending`), POST in the background, then reconcile to the real id; on failure
  mark it `__failed` (red) and restore the text. Don't reintroduce blocking `await`-before-clear.
- **Pagination differs by endpoint.** The **inbox** thread uses cursor pagination
  (`GET /api/v1/inbox/conversations/{id}/messages?before|after|around`, returns a `page` object) —
  `loadOlder` prepends on scroll-up (scroll-preserved), `loadNewer` appends, `?message=<id>` opens an
  `around` window and `focusTargetMessage` jumps + flashes it (`.msg-flash`). The **team** endpoint
  is the legacy oldest-first `limit/offset` (no cursors, no total, no newest-first) — so
  `TeamChatController::latestThreadMessages` fetches a wide window and keeps the **last** slice so the
  thread opens on the newest messages. Live messages only auto-scroll when near the bottom (don't
  yank an agent reading history).

## Embeddable web-chat widget (`public/js/widget.js`)
A self-contained vanilla JS IIFE that businesses embed on their websites. It is **not** part of
the Vite/Tailwind pipeline — all styles are inline, no external CSS dependency.

Key rules:
1. **No build step** — the file is served directly from `public/js/widget.js`. Do not import it
   into Vite or add a build transform.
2. **No host-page conflicts** — all CSS is scoped under `#tekomata-widget` with inline styles.
   z-index 99999. No global selectors.
3. **No JWT / no auth** — the widget calls the Go API's public webhook
   (`POST /api/v1/webhooks/web-chat`) keyed by `site_key` (= company ID). No Laravel session.
4. **LocalStorage persistence** — visitor ID, name, and messages are stored in
   `localStorage` keyed by site_key, surviving page reloads.
5. **Polling for replies** — every 5 s while the panel is open; degrades silently if the GET
   endpoint is not available yet (404/error stops polling without error UI).
6. The embed snippet is shown to the business on the Settings → Company → "Web Chat Widget"
   card, with the company ID pre-filled and a copy button.

## Testing & verifying UI work
Keep tests on the **service/HTTP layer** (client, exceptions, locale) — that's where the value is.
**Blade/UI stories don't need unit or feature tests** unless explicitly requested. Verify a view
by rebuilding assets and a manual look — **not** with PHPUnit.

> ⚠️ **Tailwind v4 + Vite is JIT** — the CSS bundle only contains classes present in templates
> **at build time**. After editing any Blade file, run `npm run dev` (watch) or `npm run build`,
> or new utility classes silently render unstyled (a stale `public/build` makes a page look
> "broken"). This bit us on the register redesign.
