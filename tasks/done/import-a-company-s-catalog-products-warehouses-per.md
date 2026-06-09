---
name: '[STORY] Import a company''s catalog: products, warehouses, per-warehouse stock, price tiers'
priority: 2
status: done
tags:
    - catalog
    - import
    - multi-tenant
---

An owner brings their catalog into tekomata — products, the warehouses they're stocked in, how
much stock sits in each warehouse, and the price of each product per buyer tier. tekomata owns
this data (it's imported, not synced from an ERP), and it's the data the assistant will later look
up to answer "do we have product X, where, how much, at what price?". Everything is scoped to the
owner's active company. Without this there is nothing for the core loop to look up.

## Backend
**Goal:** model and persist a company's catalog — products, warehouses, per-warehouse stock, and
per-tier prices — and accept it via a bulk import that the owner can re-run safely.

### Catalog schema: products, warehouses, stock, price tiers, prices — _engineer_
- **Goal:** the company-scoped data model the whole core loop reads against.
- **Background:** every table belongs to a `company` (the tenant) and is isolated per company.
`products` (company-scoped, identified by a per-company SKU/code + name), `warehouses`
(company-scoped, name + location), **stock per product per warehouse** (the same product can sit
in several warehouses with different quantities), `price_tiers` (named buyer levels per company,
e.g. retail / wholesale / distributor), and a **price per product per tier**. This is the shape
the wedge client needs: multiple warehouses, stock tracked per warehouse, multiple price tiers.

### `POST /catalog/import` — bulk import for the active company — _engineer_
- **Goal:** take a catalog payload (the spreadsheet/CSV a distributor already keeps) and upsert it
into the active company's catalog in one operation.
- **Background:** scoped to the caller's active company. **Idempotent** — re-importing updates
existing records by SKU rather than duplicating, so the owner can correct a sheet and re-upload.
Resolves/creates the warehouses and price tiers referenced by the rows. Validation is row-level:
bad rows are reported and skipped without corrupting the rest. Available to both the Laravel
panel (user session) and machine callers (API key) — same endpoint, attributed to the company.

### Import parser + validator — _ai-agent_
- **Goal:** turn raw import rows into validated catalog records, isolated from HTTP and storage.
- **Background:** a pure transform (keep it small and single-purpose, the house style): parse each
row into product / warehouse-stock / tier-price records, normalize SKUs, and surface per-row
errors (missing SKU, unknown/blank warehouse, non-numeric quantity or price). No DB or HTTP
concerns — it just maps input to validated records + errors so the endpoint stays thin.

### `GET /catalog` — read back the imported catalog — _ai-agent_
- **Goal:** let the panel (and a human) see what was imported to confirm it.
- **Background:** lists the active company's products with where their stock lives (per warehouse)
and their per-tier prices. Scoped to the active company; serves the panel's browse view and is a
precursor to the dedicated lookup functions built in the next story.

## Frontend
**Goal:** a control-panel screen where the owner uploads their catalog and confirms it landed.

### Catalog import route + controller — _ai-agent_
- **Goal:** accept the uploaded file and drive the import.
- **Background:** posts the file to the Go `/catalog/import` for the active company and renders the
result — counts imported/updated and any row-level errors returned.

### Catalog import + browse view — _engineer_
- **Goal:** the UI to upload a catalog and review the result.
- **Background:** an upload control (with a clear note on the expected columns / a downloadable
template) plus a summary of what imported and which rows failed and why. Below it, a browse of the
current catalog — products, per-warehouse stock, and tier prices — so the owner verifies the data
before the assistant starts answering from it.

## Acceptance criteria
- [x] An owner can import a catalog for their active company; products, warehouses, per-warehouse
stock, and per-tier prices are all persisted and scoped to that company.
- [x] Stock is tracked per product per warehouse — the same product can exist in multiple
warehouses with different quantities.
- [x] Multiple price tiers are supported, and each product can carry a different price per tier.
- [x] Re-importing is idempotent — existing records update by SKU instead of duplicating.
(Verified live: re-import → `products_updated`, `stock_movements=0`; changing a quantity appends
exactly one correcting movement.)
- [x] Invalid rows (missing SKU, unknown warehouse, non-numeric quantity/price) are reported with
clear row-level errors and skipped, without corrupting the successfully imported rows.
- [x] Catalog data is isolated per company — every query is scoped by the `company_id` from the
Principal; warehouses/tiers are resolved within the active company only.
- [x] The owner can view the imported catalog (products, where stock lives, per-tier prices) to
confirm the import (`GET /catalog`).
- [~] The import endpoint works for both a logged-in panel user and an API-key caller, attributed
to the owning company. **JWT path done; API-key path deferred** with the repo-wide API-key auth
middleware (still a stub). No contract change needed when it lands — movements already carry a
nullable actor.

## Implementation notes (done)
- Migration `00008_price_tiers.sql`: `price_tier` (company-scoped, name unique per company ci,
  soft-deleted) + `product_price` (`UNIQUE(product_id, price_tier_id)`, price in the product's
  currency). No change to `product`/`warehouse`/`stock_movement`.
- **Stock is SET, not added:** import appends one correcting `stock_movement`
  (`target − current_balance`, reason `import`) computed in SQL — zero delta inserts nothing, so
  re-import is idempotent and no Go float math touches money/quantities.
- Pure parser `parseImportRows` (usecase, reuses the shared decimal validators) → `ImportUsecase`
  (drops non-enabled-currency rows before the write tx) → `CatalogImportRepository` (one tx,
  upsert-by-SKU, resolve-or-create warehouses/tiers cached per batch) + `CatalogReadRepository`
  (3 bulk queries, no N+1).
- `go vet` + `go build` + `go test ./...` green. (`make lint`/`-race` blocked by a pre-existing
  env issue: golangci-lint v1.61.0 won't compile under Go 1.25; no gcc for cgo/race.)

> **Frontend (this repo) — done.**
> Implemented: `CatalogImportApi` service (`import` + `browse`), `CatalogImportController`
> (CSV parse → POST `/api/v1/catalog/import` → redirect with flash result; GET `/api/v1/catalog`
> browse with search), `resources/views/catalog/import.blade.php` (upload form + column notes +
> downloadable template + import result summary with per-row errors table + catalog browse with
> per-warehouse stock and per-tier prices), routes `GET/POST /catalog/import`, sidebar "Catalog"
> nav entry, and i18n strings in both `lang/`.

---

## API contract — frontend handoff (build in parallel)

> Full design + diagrams: `documentation/features/catalog-import.md`.
> Executable source of truth: Postman → folder **"Catalog Import"**.
> Both routes: `/api/v1`, **Bearer JWT**, tenant-scoped (company from the Principal).

**Scope note:** API-key auth is still a repo-wide stub, so these routes ship **JWT-only** for now
(consistent with all product/stock/category routes). They join the API-key path with no contract
change once that middleware lands — the AC "works for an API-key caller" is deferred with it.

### `POST /catalog/import`  → `200` (partial success)
Bulk upsert of products (by `sku`), warehouses + price tiers (resolve-or-create by name),
per-warehouse stock, and per-tier prices. **Idempotent**: re-running updates by SKU and *sets*
stock to the stated quantity per warehouse (applied as a correcting `stock_movement`, reason
`import`) — no double-counting. Bad rows are skipped and reported; good rows still commit.

Request:
```json
{ "rows": [
  { "sku": "AQ-600", "name": "Aqua 600ml", "unit": "piece", "is_fractional": false,
    "currency_code": "IDR", "default_price": "3500.00",
    "warehouses": [ { "warehouse": "Main", "quantity": "120" } ],
    "prices": [ { "tier": "retail", "price": "3500.00" }, { "tier": "wholesale", "price": "3200.00" } ] }
] }
```
`200`:
```json
{ "data": {
  "summary": { "rows_total": 1, "rows_succeeded": 1, "rows_failed": 0,
    "products_created": 1, "products_updated": 0, "warehouses_created": 1,
    "tiers_created": 2, "stock_movements": 1, "prices_upserted": 2 },
  "errors": [] } }
```
Per-row failure (skipped, reported in `data.errors[]`, still `200`):
```json
{ "data": { "summary": { "rows_total": 2, "rows_succeeded": 1, "rows_failed": 1, "...": "..." },
  "errors": [ { "row": 2, "field": "sku", "code": "validation.required", "message": "is required" } ] } }
```
Envelope `422` (no rows at all):
```json
{ "error": { "code": "validation_failed", "message": "one or more fields are invalid",
  "request_id": "c0ffee…",
  "fields": [ { "field": "rows", "code": "validation.required", "message": "is required" } ] } }
```
Per-row error codes: `validation.required`, `validation.decimal`, `validation.invalid_value`,
`validation.fraction_not_allowed`, `company.currency_not_enabled`.

### `GET /catalog?search=&limit=&offset=`  → `200`
Lists products with per-warehouse stock and per-tier prices (the browse/confirm view).
```json
{ "data": { "products": [
  { "id": "1f3e…", "sku": "AQ-600", "name": "Aqua 600ml", "unit": "piece", "is_fractional": false,
    "currency_code": "IDR", "default_price": "3500.0000",
    "stock":  [ { "warehouse_id": "9a…", "warehouse_name": "Main", "balance": "120.0000" } ],
    "prices": [ { "tier_id": "c1…", "tier_name": "retail", "price": "3500.0000" } ] }
] } }
```
