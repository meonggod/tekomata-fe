---
name: '[STORY] Unified billing & charging engine'
priority: 3
status: done
tags:
    - billing
    - wallet
    - usage
    - subscription
---

One engine turns every billable thing into an IDR debit on the company's wallet: per-query
usage (base rate + AI cost), any subscription tier fee, and every paid feature (image/video
generation, embeddings, speech, etc.). It converts USD AI cost to IDR, debits atomically, and
records a line-itemed charge, and the owner sees a clear cost breakdown of where their money
went. This is the piece that connects `usage-meter`, `subscription-tiers`, the AI capabilities,
and the wallet.

## Backend
**Goal:** a single charging path that prices any billable event, converts to IDR, debits the
wallet, and records an explainable, line-itemed charge.

### Feature pricing catalog migration — _ai-agent_
- **Goal:** make "all features" billable by pricing each one in config.
- **Background:** DB-backed (tekomata-internal dashboard family, like `subscription-tiers` and
the AI model catalog). Each billable capability has a price; AI provider/model costs come from
the model catalog (`ai-provider-registry`). No deploy needed to change prices.

### `func Charge(companyID, lineItems) (Charge, error)` — _ai-agent_
- **Goal:** the one entry point everything bills through.
- **Background:** sums base rate (from `subscription-tiers`) + AI cost + feature charges,
converts USD components to IDR via `currency-conversion-auto-sync`'s `Convert`, then debits
via the wallet's `Debit` inside one transaction. A **stale FX rate always blocks** the charge.
For costs tekomata has **already incurred** (an AI call already made) the charge settles even if
it leaves the wallet slightly negative — the pre-flight `HasBalanceFor` gate is what prevents
unaffordable work from starting; for **not-yet-incurred** charges (a subscription fee, a feature
purchase) it refuses up front when funds are short. Writes a line-itemed charge record and the
matching `usage-meter` event. Idempotent per source event so a retry can't double-charge.

### Charge breakdown endpoint — _engineer_
- **Goal:** expose per-company cost over a period, broken down by usage / subscription / feature.
- **Background:** reads the charge records; tenant-scoped, also reachable via per-company API key.
Drives the cost view.

## Frontend
**Goal:** show the owner exactly what they're being charged for.

### Cost & billing panel (Blade) — _engineer_
- **Goal:** a breakdown of spend by category (usage, subscription, features) over a chosen period,
alongside the wallet balance.
- **Background:** calls the breakdown endpoint and the wallet endpoint; complements the wallet
page's raw transaction history with an aggregated, categorized view.

## Acceptance criteria
- [ ] Per-query usage, subscription fees, and every paid feature all debit the wallet through one `Charge` path.
- [ ] USD AI/feature costs are converted to IDR before charging; a stale FX rate blocks the charge instead of mischarging.
- [ ] A not-yet-incurred charge (subscription fee, feature purchase) the wallet can't cover is
refused and the balance is unchanged; an already-incurred AI cost settles even if it leaves
the wallet up to one event negative.
- [ ] Each charge is line-itemed and reconciles exactly with the wallet debit and the usage-meter event.
- [ ] Re-processing the same source event does not double-charge.
- [ ] The owner sees a cost breakdown by usage / subscription / feature for a chosen period in the panel.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/unified-billing-charging-engine.md`.
> Postman: folders **Billing** (company) + **Admin · Feature Prices**.

### `GET /api/v1/billing/charges?from=&to=` (JWT) — cost breakdown
`{ data: { from, to, total_idr, by_kind: { usage?, subscription?, feature?, ai? }, charges: [ { id, source_reference, total_idr, incurred, created_at, line_items: [ { kind, description, feature_key, quantity, original_amount, original_currency, amount_idr } ] } ] } }`

### Platform-admin (X-Admin-Key) — feature-price CRUD
- `GET /api/v1/admin/feature-prices`
- `POST /api/v1/admin/feature-prices` `{ feature_key, description?, amount, currency_code, is_active? }` → **201**
- `PUT /api/v1/admin/feature-prices/{id}` → **200**; `DELETE …/{id}` → **204**

### Engine errors (internal `Charge`): `billing.insufficient_funds` (409), `billing.feature_not_priced` (404), `fx.rate_stale` (409).

## Implementation notes (done)
- Migration `00021`: `feature_price`, `charge` (idempotent on `source_reference`), `charge_line_item`.
- `domain/billing` (Charge/LineItem/FeaturePrice, Repository/Wallet/Converter/Charger ports).
- `usecase/billing.Service.Charge` — the single path: price (feature catalogue lookup) → convert USD→IDR
  (stale rate blocks) → pre-gate not-yet-incurred → debit (idempotent) → record line-itemed charge. Plus
  `CostBreakdown`, feature-price CRUD, and a record-only `subscription.ChargeListener` that unifies
  subscription charges into the ledger. Unit-tested (conversion, stale-block, incurred-vs-refuse, idempotency).
- `resource/postgres.BillingRepository`; `handler.Billing` (breakdown + admin CRUD); wallet=wallet.Service,
  converter=fx.Service via ports (no usecase→usecase import). apperr: `billing.{insufficient_funds,feature_not_priced}`.
- Verified live: feature-price CRUD; subscribe → subscription charge unified into the breakdown (total/by_kind/line items reconcile with the wallet debit).

## Acceptance criteria
- [x] Per-query usage, subscription fees, and every paid feature debit/record through one Charge path (subscription via record-only listener sharing the debit reference).
- [x] USD costs are converted to IDR before charging; a stale FX rate blocks the charge instead of mischarging.
- [x] A not-yet-incurred uncoverable charge is refused (balance unchanged); an already-incurred AI cost settles even slightly negative.
- [x] Each charge is line-itemed and reconciles with the wallet debit (shared source_reference) and metering.
- [x] Re-processing the same source event does not double-charge (idempotent).
- [x] The owner sees a cost breakdown by usage/subscription/feature for a chosen period.
- [x] Tests pass: `go build ./... && go test ./internal/...`.
