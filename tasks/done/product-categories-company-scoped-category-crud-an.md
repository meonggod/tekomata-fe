---
name: '[STORY] Product categories: company-scoped category CRUD and many-to-many product grouping'
priority: 2
status: done
tags:
    - catalog
    - categories
    - multi-tenant
    - products
---

An owner organizes the catalog by grouping products into categories — "Beverages", "Cleaning",
"Spare parts" — so the catalog is browsable and the assistant can answer by group ("what drinks do
we stock?"). A product can sit in **many** categories and a category holds **many** products
(many-to-many). Categories belong to the company that creates them. This is the grouping layer the
CRUD-product story attaches products to.

## Backend
**Goal:** company-scoped categories with full CRUD, and a many-to-many link to products that can be
managed and browsed.

### Category schema — _engineer_
- **Goal:** the company-scoped grouping entity products attach to.
- **Background:** a `categories` table owned by a `company` (tenant-isolated). Fields: `name`, an
optional `slug`/`code` for stable reference, optional `description`, `is_active` (archive without
losing the grouping), and timestamps. Names are unique within a company. **This story also creates
the `product_categories` join table (product, category) — the CRUD-product and other stories
reference this join but do not create it.** The join gives a clean many-to-many in both directions.

### `POST/GET/PUT/DELETE /categories` — category CRUD for the active company — _engineer_
- **Goal:** full create / read / update / delete of a category within the active company.
- **Background:** scoped to the caller's active company; serves both the Laravel panel (user JWT)
and machine callers (API key). Deleting a category that still has products only removes the
groupings (the join rows), never the products themselves — prefer archive over hard delete.

### Assign products to categories — _engineer_
- **Goal:** add and remove the many-to-many links between products and categories.
- **Background:** manage the `product_categories` join for the active company — set a product's full
set of categories (from the product side) and/or add/remove products on a category. Idempotent:
re-assigning an existing pair is a no-op. Tenant-scoped; both the product and the category must
belong to the active company.

### `GET /categories/{id}/products` — browse a category's products — _ai-agent_
- **Goal:** read back the products grouped under a category.
- **Background:** lists the active company's products in a category (supports the panel's browse
view and category-scoped lookups). Read-only; scoped to the active company.

## Frontend
**Goal:** a control-panel screen to manage categories and see what's grouped under each.

### Category management route + controller — _ai-agent_
- **Goal:** drive category CRUD and product grouping from the panel.
- **Background:** posts to the Go `/categories` endpoints for the active company and renders the
results — created/updated categories and validation errors.

### Category list/edit + grouped-products view — _engineer_
- **Goal:** the UI to create/edit categories and review the products in each.
- **Background:** a list of the company's categories with create/edit/archive, and per category a
view of the products it holds (with a way to add/remove products). Product-side assignment (the
multi-select to put a product in categories) lives in the CRUD-product edit screen.

## Acceptance criteria
- [x] An owner can create, view, update, and archive/delete categories scoped to their active
company; names are unique within the company.
- [x] A product can belong to many categories and a category can hold many products (many-to-many).
- [x] Products can be assigned to and removed from categories from either side, and re-assigning an
existing pair is a no-op (`ON CONFLICT DO NOTHING`; remove is idempotent).
- [x] Deleting/archiving a category removes only its groupings, never the underlying products
(verified: product still `200` after its category is deleted).
- [x] The owner can browse the products grouped under a category (`GET /categories/{id}/products`).
- [x] ~~... work for ... an API-key caller~~ — **JWT verified**; API-key path **deferred** (no
API-key auth middleware yet), consistent with the rest of the app.
- [x] Categories and groupings are isolated per company — verified another company's token gets
`404` assigning to a foreign product; the join is validated for company ownership on both sides.
- [x] Tests pass: `go vet` + `go test ./...` green (`make check`'s `lint`/`-race` steps are blocked
by this environment only — golangci-lint vs Go 1.25, and `-race` needs cgo).

> **Frontend (this repo) — done.**
> Implemented: `CategoryApi` service; `CategoryController` (index, create, store, show, edit,
> update, destroy, addProducts, removeProduct); `ProductController::updateCategories()` +
> `CategoryApi` injected into `ProductController::edit()` for product-side assignment;
> Blade views (categories: index, create, edit, show; product edit updated with category
> multi-select); routes registered under `auth.api`; i18n keys + error codes in both languages;
> Categories link added to products index nav.

---

## API contract — frontend handoff (build in parallel)

All routes under `/api/v1`, **Bearer JWT**, tenant-scoped (active `company_id` from the
Principal, never the body). Full diagrams + examples in
`documentation/features/product-categories.md`; Postman → "Product Categories".

### Categories
| Method | Path | Body | Success |
|---|---|---|---|
| POST | `/categories` | `{name, code?, description?, is_active?}` | `201 {data: Category}` |
| GET | `/categories?search=` | — | `200 {data:{categories:[Category]}}` |
| GET | `/categories/{id}` | — | `200 {data: Category}` |
| PUT | `/categories/{id}` | `{name, code?, description?, is_active?}` | `200 {data: Category}` |
| DELETE | `/categories/{id}` | — | `204` (archives + drops groupings, keeps products) |

`Category` = `{ id, name, code|null, description, is_active, created_at, updated_at }`.

### Assignment
| Method | Path | Body | Success |
|---|---|---|---|
| GET | `/categories/{id}/products` | — | `200 {data:{products:[Product]}}` |
| POST | `/categories/{id}/products` | `{product_ids:[…]}` | `204` (idempotent add) |
| DELETE | `/categories/{id}/products/{productId}` | — | `204` (idempotent remove) |
| PUT | `/products/{id}/categories` | `{category_ids:[…]}` | `200 {data:{categories:[Category]}}` (replace set) |
| GET | `/products/{id}/categories` | — | `200 {data:{categories:[Category]}}` |

`GET /products/{id}` (products feature) now also returns a `categories` array.

### Example success — `POST /categories`
```json
{ "data": { "id": "ac9…", "name": "Beverages", "code": "BEV", "description": "drinks",
  "is_active": true, "created_at": 1781004576297, "updated_at": 1781004576297 } }
```

### Example error — duplicate name (`POST /categories`)
```json
{ "error": { "code": "category.name_taken",
  "message": "category name already in use for this company", "request_id": "c0ffee…" } }
```

### Other error responses
| Situation | Status | `code` |
|---|---|---|
| Category not found (or other tenant) | 404 | `category.not_found` |
| Duplicate category name in company | 409 | `category.name_taken` |
| Referenced product not in company | 404 | `product.not_found` |
| Empty `product_ids` on add | 422 | field `validation.required` |
| No / invalid access token | 401 | `unauthorized` |
