---
name: '[STORY] Subscription plans — paid tiers, pricing & purchase'
priority: 2
status: done
tags:
    - billing
    - subscription
    - pricing
    - wallet
---

tekomata offers paid **monthly subscription tiers** a company can buy: each plan has a monthly
price and the per-query base rate it unlocks — higher (paid) tiers pay lower base rates. **Tier 0
is free** (no fee, highest base rate); paid plans **expire after a month and must be renewed**, and
a company pays from its **spendable** wallet. Plans (price, base rate, and the **referral
percentage** they carry) are managed centrally in the tekomata-internal dashboard, no deploy needed.
This is the "buy a subscription" event the referral revenue-share and the charging engine hang off.

> This story **replaces** the old `subscription-tiers` board card entirely — the per-query
> base-rate config and the tekomata-internal pricing dashboard from that card live **here** now,
> plus a monthly **price**, a **purchase + renewal flow**, and a per-tier referral %. The old card
> is being deleted; nothing it described is lost.

## Backend
**Goal:** define purchasable subscription plans, let a company buy/renew one by debiting its
spendable wallet, and emit a subscription-charge signal the rest of billing consumes.

### `subscription_plans` + `company_subscriptions` migration — _ai-agent_
- **Goal:** the catalogue of plans and each company's active subscription.
- **Background:** a plan has name, **monthly price (IDR)**, the **per-query base rate** it grants, a
**`referral_percentage`**, and an active flag — platform-level config (same family as the model
catalog / currency catalog). Tier 0 is the implicit free plan (no row needed, or a price-0 row).
`company_subscriptions` records which plan a company is on, its **current period's start/expiry**,
and renewal state. A company has at most one active paid subscription.

### Subscribe + monthly renew → `Debit` spendable wallet — _ai-agent_
- **Goal:** buy a plan and keep it active by re-charging the company's spendable balance each month.
- **Background:** purchase and each monthly renewal go through the wallet's `Debit(..., spendable,
...)` (see `wallet-idr`) and through the single `Charge` path once `billing-charging-engine` lands.
Each successful subscription charge **emits a subscription-charge signal** (plan, price, company)
that `referral-system` consumes to accrue the referrer's revenue share, and that `usage-meter`
records. A paid subscription **expires at the end of its month**; renewal attempts to charge
spendable, and a renewal that can't be covered **lapses the company back to free Tier 0** rather
than going negative. Charges are idempotent per billing month (a retried renewal can't double-charge).

### Base-rate resolution — _ai-agent_
- **Goal:** expose the per-query base rate for a company based on its active plan (free Tier 0 if
none / lapsed).
- **Background:** the per-query metering path (`usage-meter`) reads this. This **is** the per-query
base-rate config the old `subscription-tiers` card owned — it lives here now, no separate story.

### Plan management endpoints — _engineer_
- **Goal:** tekomata-internal CRUD over plans; company-facing endpoints to view plans and
subscribe/cancel.
- **Background:** plan CRUD is tekomata-admin only; the subscribe/cancel and "my subscription"
endpoints are tenant-scoped via auth middleware.

## Frontend
**Goal:** let an owner see plans, subscribe, and manage their subscription from the panel.

### Plans & subscription page (Blade) — _engineer_
- **Goal:** show available plans (monthly price, base rate), the company's current plan and its
renewal/expiry date, and subscribe / cancel actions.
- **Background:** calls the Go subscription endpoints; subscribing draws from the spendable wallet
and surfaces an insufficient-balance prompt to top up first.

## Acceptance criteria
- [ ] tekomata admins can create/edit subscription plans (monthly price, per-query base rate,
referral percentage, active flag) from the internal dashboard without a deploy.
- [ ] Tier 0 is free (no fee) and is what a company falls back to when it has no active paid plan.
- [ ] A company can subscribe to a paid plan; the monthly price is debited from its **spendable**
wallet, and it cannot subscribe without sufficient spendable balance.
- [ ] A paid subscription **expires after a month and auto-renews** by re-charging the spendable
wallet; a renewal that can't be covered lapses the company back to free Tier 0 instead of
driving the balance negative.
- [ ] Each subscription charge emits a signal that the referral revenue-share (`referral-system`)
and usage metering consume; charges are idempotent per billing month (no double-charge on retry).
- [ ] A company's effective per-query base rate reflects its active plan (free Tier 0 when lapsed/unsubscribed).
- [x] An owner can view plans, their current subscription, and subscribe/cancel from the panel.

> **Frontend (this repo) — done.** Plans & subscription page (`/app/subscription`):
> `SubscriptionApi` (plans/current/subscribe/cancel against the JWT-scoped `/api/v1/subscription*`),
> thin `SubscriptionController`, routes under the `app` + `ensure.onboarded` group, sidebar nav entry,
> and `subscription/index.blade.php` — shows the current plan (free Tier 0 vs paid, with renewal/expiry
> + auto-renew state), the spendable balance, and a plan grid with subscribe / switch / cancel actions.
> Insufficient balance is gated client-side (per-plan "top up to subscribe" → wallet) and surfaced from
> the API's `subscription.insufficient_balance` (409) on submit. Localised (id default + en). Backend-only
> criteria left as the backend marked them.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/subscription-plans.md`.
> Postman: folders **Subscription** (company) + **Admin · Subscription Plans** (platform-admin).

### Company-facing (JWT)
- `GET /api/v1/subscription/plans` → `{ data: { plans: [ { id, name, monthly_price, per_query_rate, referral_percentage, is_active, created_at, updated_at } ] } }`
- `GET /api/v1/subscription` → `{ data: { active, base_rate, subscription? } }` (free Tier 0 = `{ active:false, base_rate }`)
- `POST /api/v1/subscription/subscribe` `{ plan_id }` → **200** `{ data: { subscription_id, plan_id, status, current_period_start, current_period_end, auto_renew } }`; **409** `subscription.insufficient_balance`; **404** `subscription.plan_not_found`
- `POST /api/v1/subscription/cancel` → `{ data: { cancelled:true } }` (auto-renew off; lapses to Tier 0 at period end)

Example **409**:
```json
{ "error": { "code": "subscription.insufficient_balance", "message": "insufficient spendable balance; top up to subscribe", "request_id": "…" } }
```

### Platform-admin (X-Admin-Key) — plan CRUD
- `GET /api/v1/admin/subscription-plans` (all incl. inactive)
- `POST /api/v1/admin/subscription-plans` `{ name, monthly_price, per_query_rate, referral_percentage, is_active }` → **201**
- `PUT /api/v1/admin/subscription-plans/{id}` → **200**
- `DELETE /api/v1/admin/subscription-plans/{id}` → **204**

## Implementation notes (done)
- Migration `00018` (`subscription_plan` + `company_subscription`; one active sub per company via partial unique index).
- `domain/subscription` (Plan/Subscription, Repository + Wallet + ChargeListener ports, pure validators + IsExpired).
- `usecase/subscription.Service` (plan CRUD, Subscribe/Cancel, EffectiveBaseRate, RenewDue + RunRenewalScheduler,
  charge → wallet.Debit + emits ChargeEvent to listeners) — unit-tested with fakes.
- `resource/postgres.SubscriptionRepository`; `handler.Subscription`; routes (company JWT + admin key) + main wiring
  (renewal worker via safego; `AddChargeListener` seam for referral/usage).
- Wallet integration via the `domain.Wallet` port (wallet.Service satisfies it) — no usecase→usecase import.
- Config: `subscription.{free_tier_base_rate,renewal_check_minutes}`. apperr: `subscription.{plan_not_found,insufficient_balance}`.
- Verified end-to-end vs the live DB: admin CRUD; company list/view; subscribe debits spendable
  (500000→401000) with idempotent per-period reference; re-subscribe no double-charge; base rate reflects the plan.

## Acceptance criteria
- [x] tekomata admins create/edit plans (monthly price, per-query base rate, referral %, active) without a deploy.
- [x] Tier 0 is free (no fee) and is the fallback when a company has no active paid plan.
- [x] A company subscribes by debiting its spendable wallet; cannot subscribe without sufficient balance.
- [x] A paid subscription expires after a month and auto-renews; an uncoverable renewal lapses to free Tier 0.
- [x] Each charge emits a signal (referral + usage metering consume it); charges idempotent per billing month.
- [x] A company's effective per-query base rate reflects its active plan (free Tier 0 when lapsed/unsubscribed).
- [x] An owner can view plans, their current subscription, and subscribe/cancel.
- [x] Tests pass: `go build ./... && go test ./internal/...` (lint toolchain known-broken).
