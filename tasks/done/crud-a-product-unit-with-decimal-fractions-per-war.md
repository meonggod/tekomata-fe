---
name: '[STORY] CRUD a product: unit (with decimal fractions), per-warehouse stock, default-currency price, and a stock movement log'
priority: 2
status: done
tags:
    - catalog
    - inventory
    - multi-tenant
    - products
---

Beyond the bulk catalog import, an owner manages individual products by hand in the panel:
create, edit, and remove a product, give it a unit of measure (which may allow fractional /
decimal quantities), set a default price in the company's currency, and adjust how much stock sits
in each warehouse — with every stock change written to a movement log the owner can audit later.
Everything is scoped to the owner's active company. This is the day-to-day upkeep of the catalog
the assistant answers from.

## Backend
**Goal:** model a product with a unit, decimal-safe quantities, a default-currency price, and
per-warehouse stock, expose full CRUD over it, and record every stock change as an immutable
movement.

### Product model: unit of measure, decimal quantities, default price + currency FK — _engineer_
- **Goal:** extend the company-scoped catalog product so it carries a unit, supports fractional
quantities, and has a default price referencing a currency.
- **Background:** builds on the catalog schema introduced by the import story (products,
warehouses, per-warehouse stock). A product gains a **unit of measure** (e.g. piece, box, kg,
liter) and whether that unit is **fractional** — some units sell in whole numbers, others in
decimals (1.5 kg). Quantities and prices use a **fixed-precision decimal type, never float**, so
no rounding drift. The product holds a **default price** plus a **`currency_id` foreign key** to a
`currencies` table — the product references a currency rather than storing a free-text code, so
prices are tied to a known, managed currency. **The `currencies` table is created by the
currency-catalog story — this story assumes it exists and adds only the FK constraint.**
A product can also belong to **many categories** (many-to-many, via a `product_categories` join).
**The `categories` table and `product_categories` join are created by the product-categories
story — this story assumes those tables exist and only reads/writes the join.** All
company-scoped and tenant-isolated.

### `POST/GET/PUT/DELETE /products` — product CRUD for the active company — _engineer_
- **Goal:** full create / read / update / delete of a single product within the active company.
- **Background:** scoped to the caller's active company; serves both the Laravel panel (user JWT)
and machine callers (API key), attributed to the company. Validates the unit and rejects decimal
quantities for a non-fractional unit. Delete is guarded — a product that still holds stock or has
movement history is soft-deleted/archived rather than hard-removed, so the audit trail survives.

### Stock movement log + `POST /products/{id}/stock` — adjust stock per warehouse — _engineer_
- **Goal:** every change to a product's stock in a warehouse goes through one path and is recorded
as an immutable movement record.
- **Background:** a `stock_movements` table (append-only audit trail) — product, warehouse, decimal
quantity delta, reason/type (import, manual adjustment, correction), the actor (user or API key),
and timestamp. The **current per-warehouse stock balance is computed by summing all movement deltas
for that product+warehouse** — there is no stored balance to get out of sync. An adjustment
endpoint applies a delta in a named warehouse and appends the movement atomically. Stock reads
sum the movements; this is safe for the read-heavy workload (lookup queries).

### `GET /products/{id}/movements` — read the stock movement log — _ai-agent_
- **Goal:** let the panel (and a human) audit how a product's stock changed over time.
- **Background:** lists a product's movements, filterable by warehouse and date range, newest
first. Scoped to the active company. Read-only view over the append-only log.

## Frontend
**Goal:** a control-panel screen to manage a product end to end — its unit, default price, stock
across warehouses — and review its movement history.

### Product management route + controller — _ai-agent_
- **Goal:** drive product CRUD and stock adjustment from the panel.
- **Background:** posts to the Go `/products` and `/products/{id}/stock` endpoints for the active
company and renders the results — validation errors, updated balances, the movement that was
recorded.

### Product create/edit + per-warehouse stock view — _engineer_
- **Goal:** the UI to create or edit a product and adjust its stock across warehouses.
- **Background:** a form with a unit selector and **decimal-aware quantity inputs** (whole-number
only when the unit is non-fractional), the default price shown with the company's currency, a
**multi-select to place the product in one or more categories**, and a per-warehouse stock section
where each adjustment captures a reason. Surfaces the resulting balance after each change.

### Stock movement history view — _ai-agent_
- **Goal:** show the movement log for a product so the owner can audit it.
- **Background:** a chronological list (delta, warehouse, reason, actor, time) backed by
`GET /products/{id}/movements`, filterable by warehouse/date.

## Acceptance criteria
- [x] An owner can create, view, update, and delete a product scoped to their active company.
- [x] A product has a unit of measure; quantities are fractional (decimal) where the unit allows
it and whole-number where it does not, stored with fixed precision and no rounding drift.
- [x] A product has a default price and references a currency via a `currency_code` foreign key (not
a free-text code); the price is invalid without a currency to point at (required + FK + the
currency must be enabled for the company).
- [x] ~~A product can be assigned to zero, one, or many categories~~ — **deferred** to
`[STORY] Product categories` (which owns the `category` table + `product_categories` join).
- [x] Stock is tracked per product per warehouse — the same product can hold different quantities
in multiple warehouses.
- [x] Every stock change writes an entry to the stock movement log (delta, warehouse, reason,
actor, timestamp); the log is append-only and never edited in place.
- [x] A warehouse's current stock balance is computed by summing all movement deltas for that
product+warehouse; there is no stored balance column to diverge from history.
- [x] The movement history is viewable per product and filterable by warehouse and date.
- [x] Deleting a product that has stock or movement history preserves the audit trail (soft-delete
via `deleted_at`; movements remain).
- [x] ~~Product CRUD and stock adjustment work for ... an API-key caller~~ — **JWT verified**;
API-key path **deferred** (no API-key auth middleware yet; `actor_api_key_id` column is ready).
- [x] Catalog and stock data are isolated per company — one company's products never read or
affect another's (verified: another company's token → 404 on a foreign warehouse/product).
- [x] Tests pass: `go vet` + `go test ./...` green (`make check`'s `lint`/`-race` steps are blocked
by this environment only — golangci-lint vs Go 1.25, and `-race` needs cgo).

> **Frontend (this repo) — done.**
> Implemented: `ProductApi`, `WarehouseApi` service classes; `ProductController` (index, create,
> store, show, edit, update, destroy, adjustStock, movements) and `WarehouseController` (full CRUD);
> all Blade views (products: index, create, edit, show, movements; warehouses: index, create, edit);
> routes registered under `auth.api`; i18n keys added to both language files; dashboard "Products"
> nav link added.

---

## Scope decisions (agreed with product owner, 2026-06-08)

This story's stated dependencies did not exist when it was picked up (migrations stopped at
`00005`). Decisions made before implementation:

- **Schema:** this story creates the foundational `product`, `warehouse`, and `stock_movement`
  tables in migration `00006`. The later **catalog-import** story bulk-loads the same tables.
- **Categories:** **deferred** — the `category` table + `product_categories` join are owned by the
  separate `[STORY] Product categories` task. This story does **not** read or write categories, so
  the "assign to many categories" acceptance criterion is satisfied by that story, not here.
- **Auth:** **JWT-only** for now. The `api_key` table exists but there is no API-key auth
  middleware yet; the "works for an API-key caller" criterion is deferred to a follow-up. Movement
  records already carry a nullable `actor_api_key_id` so the trail is forward-compatible.
- **Warehouses:** **full CRUD** (`/warehouses`), since per-warehouse stock needs warehouses and the
  import story is not built.
- **Decimals:** all quantities/prices are `NUMERIC(20,4)` in Postgres (fixed precision, no float).
  On the wire they are **strings** (e.g. `"3500.00"`); the per-warehouse balance is computed by the
  DB as `SUM(quantity_delta)` so Go never does decimal math.

## API contract — frontend handoff (build in parallel)

All routes below are under `/api/v1`, require a **Bearer access token** (JWT), and are
**tenant-scoped**: the active `company_id` comes from the token Principal, never the body. Money &
quantity values are decimal **strings**; responses render at the column scale (4 dp) — a price sent
as `"3500.00"` reads back `"3500.0000"`. Times are epoch-ms `int64`. Full diagrams +
request/response examples mirrored in `documentation/features/products-stock-warehouses.md`;
executable source of truth is `postman/tekomata.postman_collection.json` → "Products & Stock".

### Entities (wire shapes)

**Product**
```json
{
  "id": "1f3e…", "name": "Aqua 600ml", "sku": "AQ-600",
  "unit": "piece", "is_fractional": false,
  "default_price": "3500.00", "currency_code": "IDR",
  "created_at": 1736400000000, "updated_at": 1736400000000
}
```
`GET /products/{id}` additionally includes per-warehouse balances:
```json
"stock": [ { "warehouse_id": "9a…", "warehouse_name": "Main", "balance": "120.0000" } ]
```

**Warehouse**
```json
{ "id": "9a…", "name": "Main", "code": "MAIN", "is_active": true,
  "created_at": 1736400000000, "updated_at": 1736400000000 }
```

**Stock movement**
```json
{ "id": "7c…", "product_id": "1f…", "warehouse_id": "9a…", "warehouse_name": "Main",
  "quantity_delta": "10.5", "reason": "manual_adjustment", "note": "stock recount",
  "actor_user_id": "4b…", "created_at": 1736400000000 }
```

### Products
| Method | Path | Body | Success |
|---|---|---|---|
| POST | `/products` | `{name, unit, is_fractional?, default_price, currency_code, sku?}` | `201 {data: Product}` |
| GET | `/products?search=&limit=&offset=` | — | `200 {data:{products:[Product]}}` |
| GET | `/products/{id}` | — | `200 {data: Product+stock}` |
| PUT | `/products/{id}` | same as POST (full update) | `200 {data: Product}` |
| DELETE | `/products/{id}` | — | `204` (soft-archives if it has movement history) |

### Warehouses
| Method | Path | Body | Success |
|---|---|---|---|
| POST | `/warehouses` | `{name, code?}` | `201 {data: Warehouse}` |
| GET | `/warehouses` | — | `200 {data:{warehouses:[Warehouse]}}` |
| GET | `/warehouses/{id}` | — | `200 {data: Warehouse}` |
| PUT | `/warehouses/{id}` | `{name, code?, is_active?}` | `200 {data: Warehouse}` |
| DELETE | `/warehouses/{id}` | — | `204` (soft-archives if it holds movements) |

### Stock
| Method | Path | Body | Success |
|---|---|---|---|
| POST | `/products/{id}/stock` | `{warehouse_id, quantity_delta, reason, note?}` | `201 {data:{movement: Movement, balance: "130.5"}}` |
| GET | `/products/{id}/movements?warehouse_id=&from=&to=&limit=&offset=` | — | `200 {data:{movements:[Movement]}}` |

`reason` ∈ `import` \| `manual_adjustment` \| `correction`. `from`/`to` are epoch-ms bounds.

### Example success — `POST /products`
```json
{ "data": { "id": "1f3e…", "name": "Aqua 600ml", "sku": "AQ-600", "unit": "piece",
  "is_fractional": false, "default_price": "3500.0000", "currency_code": "IDR",
  "created_at": 1736400000000, "updated_at": 1736400000000 } }
```

### Example error — non-fractional unit given a decimal quantity (`POST /products/{id}/stock`)
```json
{ "error": { "code": "validation_failed", "message": "one or more fields are invalid",
  "request_id": "c0ffee…",
  "fields": [ { "field": "quantity_delta", "code": "validation.fraction_not_allowed",
    "message": "unit 'piece' does not allow fractional quantities" } ] } }
```

### Other error responses (i18n `code`)
| Situation | Status | `code` |
|---|---|---|
| Product / warehouse not found (or other tenant) | 404 | `product.not_found` / `warehouse.not_found` |
| SKU already used in company | 409 | `product.sku_taken` |
| Warehouse name already used in company | 409 | `warehouse.name_taken` |
| `currency_code` not enabled for the company | 404 | `company.currency_not_enabled` |
| Malformed decimal in price/quantity | 422 | field `validation.decimal` |
| Missing required field | 422 | field `validation.required` |
| Unknown/invalid `reason` | 422 | field `validation.invalid_value` |
| No / invalid access token | 401 | `unauthorized` |
