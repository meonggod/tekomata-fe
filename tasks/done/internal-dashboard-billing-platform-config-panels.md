---
name: '[STORY] Internal dashboard — billing & platform config panels'
priority: 2
status: done
tags:
    - internal-dashboard
    - admin
    - billing
    - config
---

tekomata staff configure all the money-moving and platform-catalog knobs from `/internal/*` — the
settings the Billing & monetization epic's shipped cards each said are "managed in the internal
dashboard, no deploy needed." This story delivers those panels and the admin endpoints behind them.
**Depends on `internal-dashboard-foundation`** (the staff principal, the `/internal/*` guard, the
audit log, and the console shell these panels mount into).

## Backend
**Goal:** expose the billing/platform config endpoints — behind the foundation's staff guard, writing
through its audit log — for plans, feature pricing, FX, promos, referral caps, payouts, and
country/currency flags.

### Mount/expose the billing & platform config endpoints — _engineer_
- **Goal:** give each shipped billing/platform feature's "tekomata-admin only" config a real, guarded
home, adding the thin admin endpoints any card assumed but never exposed.
- **Background:** several cards specced admin CRUD (e.g. `subscription-plans`' "Plan management
endpoints") that assumed a staff guard that didn't exist. Route them under `/internal/*` behind the
foundation guard and through its audit log, covering: **subscription plans** (price, base rate,
referral %, active), **feature pricing catalog** (`billing-charging-engine`), **FX rate** status +
manual refresh + staleness max-age (`auto-sync-fx-rates`), **promo codes** (amount/window/cap/active),
**referral caps/eligibility** (the % itself is per plan), **wallet payout** minimum/fee
(`wallet-idr`), and **country/currency platform active flags** (`countries-currencies-catalog`). All
DB-backed so changes need no deploy.

## Frontend
**Goal:** one panel per billing/platform config surface, plugged into the console shell.

### Billing & platform config panels (Blade) — _engineer_
- **Goal:** a working screen per surface, each reading/writing its Go admin endpoint.
- **Background:** panels plug into the foundation's role-gated nav; mutate controls show only for
authorized roles. Panels:
- **Subscription plans** — monthly price, per-query base rate, referral %, active flag.
- **Feature pricing** — per billable capability price.
- **FX rates** — current USD→IDR rate, freshness/staleness, the staleness max-age guard, manual refresh.
- **Promo codes** — amount, validity window, usage cap, active flag.
- **Referral settings** — caps / eligibility.
- **Wallet payout settings** — withdrawal minimum / fee.
- **Countries & currencies** — platform-level active flags.

## Acceptance criteria (backend)
- [x] All endpoints sit behind the foundation's `/internal/*` staff guard; reads any staff, money-moving
writes superadmin-only (`RequireStaffMutate`). *(verified)*
- [x] Working endpoints per surface — subscription plans, feature pricing, promo codes, FX rates/sync
(re-exposed under `/internal/*` by the foundation); **wallet payout min/fee + referral cap + FX max-age**
via the new `GET/PUT /internal/platform-settings`; **country/currency active flags** via
`GET /internal/{countries,currencies}` + `PUT .../{code}/active`. *(verified: settings GET/PUT, toggles)*
- [x] Changes are **DB-backed** (`platform_setting` + `country/currency.is_active`) and take effect with no
deploy — the **FX staleness max-age is read live** by the charging guard (`SetMaxAgeProvider`). *(verified)*
- [x] FX panel: rate freshness via `GET /internal/fx/rates`, manual refresh `POST /internal/fx/sync`, and
the staleness max-age is settable (`PUT /internal/platform-settings/fx_max_age_hours`, typed-validated).
- [x] Every config write is recorded in the foundation's audit log (`AuditConfigChanges`).
- [x] *(Frontend — Laravel)* The panels' UI. Built in the FE repo.

> **Deferred (small follow-ons):** enforcing `wallet_payout_min`/`fee` inside the wallet withdraw path and
> `referral_reward_cap` inside the referral accrual — the values are stored, validated, and exposed via
> `platformconfig.Service` getters, ready to inject. FX max-age enforcement IS wired live.

## API contract — frontend handoff (build in parallel)
See **`documentation/features/internal-dashboard-billing-config.md`** (full contract + Mermaid + examples).
Postman: **Internal · Platform Config** folder. Migration `00028` adds `platform_setting`.

**Status:** done
