# CLAUDE.md — tekomata frontend

> Read `company.md` for the product/business context. This file is the engineering guide for
> **this repo only**: the Laravel **web control panel**.

## What this repo is
Laravel + Blade + Tailwind. **UI layer only** — it renders pages and **calls the Go API** for
all data and auth. No business logic or product DB here; the Go API is the single backend and
source of truth. (The product assistant itself runs over WhatsApp, not here.)

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
  exception), `TokenStore` (session JWT), `AuthApi`, `Exceptions/`.
- `app/Http/Middleware/` — `EnsureAuthenticated` (`auth.api` alias), `SetLocale`.
- `config/services.php` → `tekomata` — API base URL/timeouts/retries (from `.env`).
- `resources/views/` — Blade; layout at `components/layouts/app.blade.php`.
- `tasks/` — `[STORY]` docs from ClickUp (see `tasks/README.md`).

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

## Testing & verifying UI work
Keep tests on the **service/HTTP layer** (client, exceptions, locale) — that's where the value is.
**Blade/UI stories don't need unit or feature tests** unless explicitly requested. Verify a view
by rebuilding assets and a manual look — **not** with PHPUnit.

> ⚠️ **Tailwind v4 + Vite is JIT** — the CSS bundle only contains classes present in templates
> **at build time**. After editing any Blade file, run `npm run dev` (watch) or `npm run build`,
> or new utility classes silently render unstyled (a stale `public/build` makes a page look
> "broken"). This bit us on the register redesign.
