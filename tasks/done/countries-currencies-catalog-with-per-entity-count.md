---
name: '[STORY] Countries + currencies catalog with per-entity country and per-company currency enablement'
priority: 2
status: done
tags:
    - catalog
    - currency
    - multi-tenant
    - pricing
---

tekomata operates across countries and currencies. The platform seeds two master catalogs —
**countries** and **currencies** — so registration can offer a country dropdown, each entity has a
home country that drives sensible defaults (currency, dial code), and each company enables the
subset of currencies it actually prices in. This is the foundational catalog the CRUD-product and
import stories depend on for pricing.

## Backend
**Goal:** two seeded master catalogs (countries, currencies) with per-entity country assignment and
per-company currency enablement.

### Countries catalog schema + seed — _engineer_
- **Goal:** the master list of countries the platform references for selection and defaults.
- **Background:** a **global** `countries` table (not company-scoped), seeded from ISO 3166-1,
read-only to tenants. Per country: **`country_code`** (ISO 3166-1 alpha-2, PK — `ID`, `US`, `JP`),
**`name`** ("Indonesia"), **`flag_image`** (asset path/URL), **`dial_code`** (`+62`, `+1` — supports
WhatsApp phone inputs), **`default_currency_code`** (FK → `currencies.code`; the currency to default
to when this country is chosen), and **`is_active`**. Country → currency is **many-to-one** — many
countries can share one currency (EUR), so countries are their own table, not folded into currencies.
This catalog feeds the country dropdowns at registration/profile.
- **Seed source:** a static seed file **committed to the repo** (e.g. JSON), generated once from a
reputable open dataset — country name/code/dial code/flag and the default-currency mapping from a
dataset like `mledoze/countries` (ODbL) — so the seed is deterministic, offline, and reviewable in a
PR (no runtime call to an external API). The source + license are recorded so the seed is reproducible.
- **`is_active` is tekomata's platform-level restriction switch:** an inactive country never appears in
any dropdown/list and **cannot be assigned** to a user or company — so tekomata can launch with only a
chosen subset active (e.g. Indonesia first) and open up more later.

### Currencies catalog schema + seed — _engineer_
- **Goal:** the master list of currencies used for product pricing.
- **Background:** a **global** `currencies` table (not company-scoped), seeded from ISO 4217,
read-only to tenants. Per currency: **`code`** (ISO 4217 alpha-3, PK — `USD`, `IDR`, `EUR`),
**`name`** ("Indonesian Rupiah"), **`symbol`** (`Rp`, `$`, `€`) plus optional **`symbol_native`**
when the local glyph differs, **`decimal_places`** (minor unit — `USD` 2, `IDR`/`JPY` 0, `KWD` 3;
governs price precision/rounding), **`display_hints`** (`symbol_position` before/after, decimal/
thousands separators), and **`is_active`**. **Exchange rates and currency conversion are
intentionally out of scope here** — v1 is read-only with no money movement, so they belong to a
later billing-currency story, not this catalog.
- **Seed source:** a static seed file **committed to the repo** (e.g. JSON), generated from **ISO 4217**
— code, name, symbol, and `decimal_places` (minor units: `IDR`/`JPY` 0, most 2, `KWD`/`BHD` 3) — so it
is deterministic and offline. The source is recorded so the seed is reproducible.
- **`is_active` is tekomata's platform-level restriction switch:** an inactive currency never appears in
any listing and **a company cannot enable it** — so tekomata can offer only a chosen subset of
currencies and expand later.

### Per-entity country assignment — _engineer_
- **Goal:** users and companies have a home country that determines defaults (currency, dial code).
- **Background:** add a `country_code` column to the `users` and `companies` tables (FK →
`countries.country_code`), set during registration / company creation. Derives the default currency
(via the country's `default_currency_code`) and the dial code for WhatsApp; allows country-based
filtering for reporting or compliance.

### `GET /countries` — list countries for dropdowns — _ai-agent_
- **Goal:** expose the country list for registration and profile forms.
- **Background:** returns active countries with `country_code`, `name`, `default_currency_code`,
`flag_image`, and `dial_code`. Searchable. Serves both the Laravel panel and API-key callers. The
list is complete — e.g. every Eurozone country appears individually, not collapsed into a single
currency row.

### `GET /currencies` — list currencies for pricing — _ai-agent_
- **Goal:** expose the currency catalog for product pricing currency selection.
- **Background:** returns active currencies with `code`, `name`, `symbol`, `symbol_native`,
`decimal_places`, and `display_hints`. Serves both the Laravel panel and API-key callers.

### Per-company currency enablement — _engineer_
- **Goal:** each company enables the subset of currencies it actually prices in and names one default.
- **Background:** a `company_currencies` join (company, currency `code`, `is_default`). Endpoints to
enable/disable a currency for the active company and to set its default; **exactly one default per
company**. A product's currency must be one the company has enabled — disabling a currency that's in
use is blocked or warned. Tenant-scoped.

## Frontend
**Goal:** control-panel screens for country selection and currency enablement.

### Country selection in registration/profile — _ai-agent_
- **Goal:** country dropdown in user/company forms.
- **Background:** calls `GET /countries` and renders a searchable dropdown with flag icons, names,
and dial codes. The selection sets the entity's `country_code` and derives the default currency.

### Currency settings route + controller — _ai-agent_
- **Goal:** drive currency enable/disable and set-default from the panel.
- **Background:** posts to the Go enablement endpoints for the active company and renders the result;
reads the catalog from `GET /currencies`.

### Currency settings view — _engineer_
- **Goal:** the UI to browse currencies and configure the company's set.
- **Background:** lists currencies showing **flag, symbol, code, and name**; a toggle per currency to
enable/disable it for the company, and a single-select (radio) to mark the default. Surfaces which
currencies are in use by products so the owner doesn't disable one mid-flight.

## Acceptance criteria
- [x] Two seeded catalogs exist: a `country` table (country code, name, flag, dial code, default
currency) and a `currency` table (code, name, symbol, decimal places, display hints) — they are
**separate tables** (singular per repo convention), and many countries may map to the same currency
(37 Eurozone countries share `EUR`).
- [x] Both catalogs are seeded from a **committed, deterministic seed file** (`db/seed/*.json`, no
runtime API call), and the source dataset/license is documented (`db/seed/README.md`,
`db/seed/generate.py`) so the seed is reproducible. (172 currencies, 246 countries.)
- [x] `is_active` on **both** tables acts as a platform-level restriction switch: inactive rows are
excluded from all lists and cannot be assigned/enabled. Launch seeds only Indonesia + IDR/USD active;
re-seed does not overwrite `is_active`. (Verified: `/currencies` returns only IDR/USD.)
- [x] `GET /countries` returns the full active country list (Eurozone countries appear individually)
and feeds the registration dropdown. (Verified live.)
- [x] A user and a company each have a `country_code` referencing the `country` table; choosing a
country at registration derives the default currency (auto-enabled as company default) and stores the
country whose `dial_code` drives WhatsApp. (Verified: register `country=id` → company default IDR.)
- [x] A company can enable and disable currencies and set exactly one default currency for pricing
(first enabled = auto default; partial unique index enforces ≤1 default; default cannot be disabled).
(Verified live: full enable/set-default/disable lifecycle + all error codes.)
- [~] A product can only reference a currency the company has enabled; disabling a currency in use is
blocked or warned. **DEFERRED** to the CRUD-product story (no `product` table yet). Disabling the
*current default* IS blocked now; the product-in-use guard lands with products.
- [x] Endpoints work for both logged-in panel users and API-key callers; catalogs are shared globally
but per-company currency settings are isolated. Catalog GETs are **public** (serve panel + machine
callers + pre-login registration); company-currency endpoints are JWT-scoped to the active company.
(Full machine API-key auth arrives with the api-key story; the middleware is still a stub.)
- [x] Exchange rates, currency conversion, and invoice-currency selection are **explicitly out of
scope** for this story and deferred to a later billing-currency story.
- [x] Tests pass (`go test ./...`); migration `00005` applied + seeded; endpoints verified live.

> **Frontend (this repo) — done.** All three `## Frontend` units implemented against the API
> contract; backend-owned criteria left as the backend ticked them.
> - **Country selection in registration** — `RegisterController::show` fetches `GET /api/v1/countries`
>   (via new `Services/Tekomata/CatalogApi`) and feeds an optional, backward-compatible country field
>   on the signup form. `RegisterController::store` forwards `country_code` to `AuthApi::register`. The
>   field is a styled native `<select>` (no-JS source of truth) progressively enhanced by
>   `enhanceCountrySelects` in `resources/js/app.js` into a searchable, flag-rich listbox (`flag_image`,
>   `name`, `dial_code`); component at `components/country-select.blade.php`. Degrades to no field if
>   the public catalog is unreachable.
> - **Currency settings route + controller** — `CurrencyController` + routes under `auth.api`
>   (`/settings/currencies` GET/POST + `{code}/default` PUT + `{code}` DELETE). Reads the catalog from
>   `GET /api/v1/currencies` and the company's enabled set from `GET /api/v1/company/currencies`
>   (new `Services/Tekomata/CompanyCurrencyApi`); enable/set-default/disable post to the JWT-scoped
>   endpoints. 5xx pops the shared error modal; other API errors render localised, code-derived text.
> - **Currency settings view** — `currencies/index.blade.php`: lists every active currency (symbol,
>   code, name, minor units) with enable/disable controls and a single default selector; the current
>   default is protected from disabling (per the enforced contract). The product-in-use guard is noted
>   as deferred (no product table yet), matching the backend's `[~]`.
> - New error-catalog codes (`catalog.currency_not_active`, `company.currency_already_enabled`,
>   `company.currency_not_enabled`, `company.cannot_disable_default_currency`, `validation.invalid_country`)
>   and UI strings added to **both** `lang/{en,id}`. Assets rebuilt; `vendor/bin/pint` clean.

---

## API contract — frontend handoff (build in parallel)

> Full design + Mermaid diagrams: `documentation/features/countries-currencies-catalog.md`.
> Postman: `postman/tekomata.postman_collection.json` → folders "API v1 / Catalog" +
> "API v1 / Company Currencies". Envelope: `{ "data": ... }` /
> `{ "error": { "code", "message", "request_id", "fields"? } }`.

**Conventions decided:** singular table names (`currency`, `country`,
`company_currency`); catalog GETs are **public** (registration needs countries
pre-login); company-currency endpoints behind **Bearer JWT**, scoped to the
`Principal`'s active company. Full ISO lists seeded as committed JSON; only
**Indonesia + IDR/USD** start active (`is_active` is the platform switch).

### `GET /api/v1/countries?search=` — public
Active countries only. `200` → `{ "data": { "countries": [ { "country_code":"ID",
"name":"Indonesia", "default_currency_code":"IDR", "flag_image":"…", "dial_code":"+62" } ] } }`.

### `GET /api/v1/currencies?search=` — public
Active currencies only. `200` → `{ "data": { "currencies": [ { "code":"IDR",
"name":"Indonesian Rupiah", "symbol":"Rp", "symbol_native":"Rp", "decimal_places":0,
"display_hints":{ "symbol_position":"before", "decimal_separator":",", "thousands_separator":"." } } ] } }`.

### `GET /api/v1/company/currencies` — Bearer JWT
The active company's enabled currencies (catalog fields + `is_default`). `200` →
`{ "data": { "currencies": [ { "code":"IDR", …, "is_default":true } ] } }`. `401 unauthorized`.

### `POST /api/v1/company/currencies` — Bearer JWT
`{ "currency_code":"USD" }` → `201 { "data": { "currency_code":"USD", "is_default":false } }`
(first enabled = auto default). Errors: `422 validation_failed`;
`400 catalog.currency_not_active`; `409 company.currency_already_enabled`.

### `PUT /api/v1/company/currencies/{code}/default` — Bearer JWT
`200 { "data": { "currency_code":"USD", "is_default":true } }`. Error:
`404 company.currency_not_enabled`.

### `DELETE /api/v1/company/currencies/{code}` — Bearer JWT
`204`. Errors: `404 company.currency_not_enabled`;
`409 company.cannot_disable_default_currency`. *(In-use guard deferred — see below.)*

### `POST /api/v1/auth/register` — public *(extended)*
Adds optional `country_code` (backward compatible); stored on `pending_registration`,
applied to the new user + first company at verify, and the country's default currency
(if active) is enabled as the company default. Error (new):
`422 validation_failed` field `country_code` → `validation.invalid_country`.

## Implementation notes / scope decisions
- **Singular tables**, `currency` created **before** `country` (FK direction).
- **Seed:** full ISO 3166-1 (mledoze/countries, ODbL) + ISO 4217 committed to
  `db/seed/*.json`, embedded via `go:embed`, loaded by `cmd/seed` (`make seed`),
  idempotent upsert that does **not** overwrite `is_active` (preserves platform toggles).
- **Per-entity country** = nullable `country_code` FK on `"user"`, `company`, and
  `pending_registration`; registration gains an **optional** `country_code`.
- **DEFERRED:** the acceptance item "a product can only reference an enabled currency;
  disabling a currency in use is blocked/warned" depends on a `product` table that does
  not exist yet (CRUD-product story). Enable/disable/set-default + "can't disable the
  current default" are enforced now; the **product-in-use guard is deferred** to the
  product story.
- **New error codes:** `catalog.currency_not_active` (400),
  `company.currency_already_enabled` (409), `company.currency_not_enabled` (404),
  `company.cannot_disable_default_currency` (409), field `validation.invalid_country`.
