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
  exception; `get`/`post`/`request` take an optional `array $headers` for custom headers), `TokenStore`
  (tenant session JWT; `userId()` decodes the access-token JWT `sub` claim — `/auth/me` is 404 and
  `user()` is never populated, so use `userId()` for the signed-in user id), `AuthApi`, `InboxApi`,
  `TeamChatApi`, `WalletApi`, `SubscriptionApi`, `BillingApi`, `ReferralApi`, `CsApi`, `ProductApi`,
  `ProductMediaApi` (product gallery; uses `TekomataClient::postMultipart` for uploads),
  `CatalogImportApi`, `Exceptions/`.
  **Staff console (`/internal/*`):** `StaffTokenStore` (the staff JWT — separate session keys from the
  tenant `TokenStore`; `email()`/`role()`/`isSuperadmin()` decoded from the access-token claims),
  `StaffAuthApi` (staff login/refresh/logout/forgot/set-password), and the `Admin/` family
  (`PlatformConfigApi`, `AiCatalogApi`, `CsReviewApi`, `StaffAdminApi`) — all staff-JWT'd against
  `/api/v1/internal/*`. See **Internal dashboard console** below.
- `app/Http/Middleware/` — `EnsureAuthenticated` (`auth.api` alias), `EnsureOnboarded`
  (`ensure.onboarded`), `EnsureStaffAuthenticated` (`internal.auth` — the staff-JWT guard for
  `/internal/*`, refresh-once-on-expiry, redirects to `internal.login`), `EnsureStaffSuperadmin`
  (`internal.superadmin` — gates money-moving writes to the `superadmin` role), `SetLocale`.
- `app/Http/Controllers/` — thin controllers: `CompanySettingsController` (settings page),
  `ProductController` (product CRUD + stock), `ProductMediaController` (product gallery — same-origin
  JSON proxy returning `{data}|{error}`, JWT server-side), `InboxController` (agent inbox +
  cursor-paginated thread), `TeamChatController` (internal team chat), `WalletController` (prepaid IDR
  wallet), `SubscriptionController` (plans + subscribe/cancel), `BillingController` (cost breakdown
  panel), `ReferralController` (referral page), `CsController` (CS-assistant proxy). **Staff console:**
  `InternalDashboardController` + `InternalFxController` and the `Controllers/Internal/` group
  (`StaffAuthController`, `BillingConfigController`, `RegionController`, `AiCatalogController`,
  `CsReviewController`, `StaffController`).
- `config/services.php` → `tekomata` — API base URL/timeouts/retries (the staff console needs no extra
  config; it authenticates with a staff login, not a shared key).
- `resources/views/` — Blade; layout at `components/layouts/app.blade.php`.
- `resources/views/settings/` — company settings (company identity, assistant, billing,
  WhatsApp numbers, web-chat widget embed code).
- `resources/views/products/` — product CRUD pages. The edit page hosts the **product media
  manager** (`partials/media-manager.blade.php`): angle-tagged photo upload, video upload, drag
  reorder, set-thumbnail, delete. See **Product media gallery** below.
- `resources/views/inbox/` — omnichannel agent inbox (conversations list + thread).
- `resources/views/team/` — internal team chat.
- `resources/views/wallet/` — prepaid IDR wallet (spendable + reward balances, top-up/convert/
  withdraw, bucket-tagged transaction history).
- `resources/views/subscription/` — paid monthly plans: current plan (free Tier 0 vs paid, with
  renewal/expiry + auto-renew), plan grid, subscribe/switch/cancel (debits the spendable wallet).
- `resources/views/billing/` — aggregated cost breakdown (usage/subscription/feature/AI) over a
  7/30/90-day window, alongside the spendable balance; complements the wallet's raw ledger.
- `resources/views/referral/` — the company's referral code + share link (copy), total reward,
  and referred-companies table; rewards land in the **reward** wallet bucket.
- `resources/views/internal/` — **tekomata-staff** console pages: `auth/` (staff login / forgot /
  set-password), `dashboard`, `billing-config`, `fx`, `regions`, `ai`, `cs`, `staff`. Shared
  presentational components in `components/internal/` (`field`, `save-button`, `active-badge`). See
  **Internal dashboard console** below.
- `resources/views/components/cs-widget.blade.php` — **CS feature-assistant** floating chat widget
  (`x-cs-widget :surface="homepage|in_app"`), on the landing page + every panel page. NOT the
  embeddable widget below — this one answers questions about *tekomata itself*. See **CS assistant**.
- `public/js/widget.js` — **embeddable web-chat widget** (standalone IIFE, no build step).
  Served at a stable URL for external `<script>` embedding. Do **not** move this into Vite.
- `resources/js/app.js` — panel JS: copy-to-clipboard, country combobox, business-hours
  configurator, inbox split-pane, team-chat split-pane, wallet, `initCatalogImport` (async import),
  `initProductMedia` (product gallery), `initCsWidget` (CS assistant) — all progressive enhancement,
  no framework. The inbox/team thread logic is the most involved — see **Messaging UI** below.
- `public/img/video-placeholder.svg` — shared black-with-video-icon tile the product gallery shows
  for videos (videos aren't resized / have no generated poster).
- `tasks/` — `[STORY]` docs from ClickUp (see `tasks/README.md`).

### URL structure (two audiences)
- `/` — public marketing + auth (`/login`, `/register`, `/verify`, `/forgot-password`, …).
- **`/app/*`** — the tenant control panel (dashboard, products, inbox, settings, …). The whole
  authenticated group is wrapped in `Route::prefix('app')`; **route names are unchanged**
  (`dashboard`, `products.index`, …), so always link with `route()`/`redirect()->route()` — never a
  hardcoded path. JS that needs a panel URL reads it from a `data-*` attribute rendered by Blade
  (`data-index-url`, `data-*-url-template`), so the prefix lives in one place. `{id}` placeholders in
  those templates are replaced client-side.
- **`/internal/*`** — the tekomata-staff console (separate audience **and** separate identity). Staff
  log in at `/internal/login` with their own account (no company membership) and get a **staff JWT**
  held server-side (`StaffTokenStore`); guard is `internal.auth` (+ `internal.superadmin` on
  money-moving writes). A tenant token is never accepted here. Not gated by onboarding. Own layouts
  `components/layouts/internal.blade.php` (signed-in console, role-gated nav) and `internal-auth.blade.php`
  (login/forgot/set-password). See **Internal dashboard console** below.

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
The `/internal/*` staff console needs **no env config** — it authenticates with a dedicated staff
login (its own JWT), not a shared key. (The old `TEKOMATA_ADMIN_KEY` / `TEKOMATA_INTERNAL_EMAILS`
stop-gaps were retired by the staff principal.)

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

## Product media gallery (`initProductMedia`) — non-obvious rules
A product carries a gallery of **angle-tagged photos + videos** with one designated thumbnail. The
manager lives on the **product edit page** (`products/partials/media-manager.blade.php`), driven by
`initProductMedia()` in `app.js`. It mirrors the inbox/import pattern: routes + i18n + csrf ride in an
embedded JSON config block (`[data-product-media-config]`), nothing hard-coded in JS.
- **The JWT never reaches the browser.** Every call goes **same-origin** to `ProductMediaController`
  (routes `products.media.{index,store,reorder,thumbnail,destroy}`), which attaches the session token
  server-side via `ProductMediaApi`. The controller returns a small `{data}|{error}` JSON envelope;
  the Go API stays authoritative for all limits — the client-side format/size checks are just
  first-line feedback.
- **Mutations re-fetch the gallery.** After upload/thumbnail/delete the JS reloads via the list route
  so the UI always reflects server truth; reorder renders straight from the PUT response (the only
  call that returns the reordered gallery), falling back to a reload on error.
- **`view` is photos-only.** Photos require a `view` (front|back|left|right|top|bottom|detail); a
  video must **not** carry one. The thumbnail must be a **photo**. Each single-angle view holds ≤1
  photo, `detail` ≤10, videos ≤5 — enforced upstream, surfaced via `errors.product_media.*`.
- **Render through the storage proxy, never a raw bucket URL.** Tiles use the API's `url` field with
  an on-the-fly `?size=` variant (see `on-the-fly-image-sizing`). A **video** tile shows the shared
  `public/img/video-placeholder.svg` (videos aren't resized / have no poster).
- **Uploads are multipart** via `TekomataClient::postMultipart` (single attempt — never blind-retry an
  upload). Controller reads bytes with `file_get_contents($file->getRealPath())` (the import pattern).
- Strings: `messages.products.media.*`; errors: `errors.product_media.*` + `errors.file.storage_unavailable`.

## Internal dashboard console (`/internal/*`) — non-obvious rules
The tekomata-staff ops console. Its defining trait: a **staff principal wholly separate from tenant
auth** — a different audience, a different login, a different JWT. Treat it as a second mini-app that
happens to share the Vite bundle and the `TekomataClient`.
- **Separate identity, separate session keys.** `StaffTokenStore` (keys `tekomata.staff.*`) holds the
  staff access/refresh pair — it never touches the tenant `TokenStore`. `email()`/`role()`/
  `isSuperadmin()` are decoded from the staff access-token claims (`sub`/`email`/`role`), same JWT
  trick as `TokenStore::userId()`. A tenant token simply isn't present under the staff keys, so a
  company user hitting `/internal/*` is bounced to the staff login — no allowlist needed.
- **Two-tier guard.** `internal.auth` (`EnsureStaffAuthenticated`, refresh-once-on-expiry → redirect
  to `internal.login`) gates every console page. `internal.superadmin` (`EnsureStaffSuperadmin`)
  further restricts **money-moving writes** to the `superadmin` role; view-only `ops` can read every
  panel but the mutate routes 403 (and the Blade hides their controls via `$isSuperadmin`). Reads are
  open to any staff; CS review + knowledge are operational (non-money) so any staff incl. `ops` may
  write them.
- **Public vs guarded routes.** `/internal/{login,logout,forgot-password,set-password}` sit OUTSIDE
  the guard (the visitor isn't signed in yet); everything else is inside the `internal.auth` group.
  Route names are all `internal.*`; always link with `route()`.
- **All staff calls go to `/api/v1/internal/*` with the staff JWT** — never a tenant JWT, never the
  retired `X-Admin-Key`. One thin service per family under `app/Services/Tekomata/Admin/`
  (`PlatformConfigApi` = plans/feature-prices/promo-codes/platform-settings/countries/currencies/fx;
  `AiCatalogApi`; `CsReviewApi`; `StaffAdminApi`). The controller passes the token in
  (`StaffTokenStore::accessToken()`) so the services stay stateless.
- **Panels degrade per-section.** Each index reads its sections inside try/catch and renders an empty
  section on failure (`BillingConfigController::safe`, the `catch (TekomataApiException)` in the
  others) — one dead endpoint never takes the whole panel down. This matters because the local Go API
  may not expose every `/internal/*` endpoint yet.
- **Server-rendered, POST-back, no SPA.** Forms post and `back()->with('status', …)`; the layout
  renders the flash. Inline edit/add forms use `<details>` toggles. Shared inputs are
  `x-internal.field` / `x-internal.save-button` / `x-internal.active-badge`. Strings live under
  `messages.internal.*`; the staff-auth + config flashes are there too.
- **FX moved here from the old admin-key path.** `InternalFxController` now reads
  `PlatformConfigApi::fxRates`/`fxSync` with the staff JWT and adds the staleness `fx_max_age_hours`
  setting (superadmin). The deleted `AdminFxApi`/`EnsureInternalStaff` + `TEKOMATA_ADMIN_KEY`/
  `TEKOMATA_INTERNAL_EMAILS` were superseded by the staff principal — don't reintroduce them.

## CS feature-assistant (`x-cs-widget`, `initCsWidget`) — NOT the embeddable widget
Two different "chat widgets" exist; do not confuse them. The CS assistant answers questions about
**tekomata's own** features/pricing; the embeddable widget (below) sits on *customers'* sites.
- `x-cs-widget :surface="homepage|in_app"` — one Blade component, on the landing page
  (`surface=homepage`, anonymous, shows a sign-up CTA) **and** in `layouts/app.blade.php`
  (`surface=in_app`, every panel page). Driven by `initCsWidget()` in `resources/js/app.js`.
- **The JWT never reaches the browser.** The widget POSTs same-origin to `/cs/ask` (`CsController`,
  a public web route); the controller attaches the session token **only** when `surface=in_app`, so
  the homepage asks anonymously (AI cost is tekomata's own) and in-app answers are company-aware.
- **API contract (two-hop proxy).** Browser → `POST /cs/ask` `{question, surface}` (`CsController::ask`)
  → Go API `POST /api/v1/cs/ask` `{question, surface}` with the optional Bearer JWT (`CsApi::ask`),
  which returns `{data:{answer, answered, confidence}}`. The controller unwraps `data` and re-envelopes
  to `{answer, answered, confidence}` for the widget. The Go API base URL is `TEKOMATA_API_URL`
  (`config/services.php` → `tekomata.base_url`). The backend handler + its docs live in the **separate
  Go API repo**, not here — this repo only owns the client side of the contract (`CsApi` docblock).
- Synchronous ask→answer (no polling). Answers are rendered with `textContent` — **never** injected
  as HTML. Honest fallback/error bubbles on an unknown question or a backend failure.

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
