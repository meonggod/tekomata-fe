---
name: '[STORY] Company referral program — subscription revenue share'
priority: 3
status: done
tags:
    - billing
    - referral
    - growth
    - wallet
---

Every **company** gets a unique referral code/link to share. When another company signs up with
that code and later **buys a subscription**, the referring company earns a configurable
**percentage of the referee's subscription price** (e.g. 10%) on **every** subscription
payment/renewal, credited to its **withdrawable reward wallet**. The reward tracks real paying
tenants — no payout on signup alone — and self-referral and double-attribution are blocked. The
percentage is configured on the **subscription plan/tier** (see `subscription-plans`), managed in
the tekomata-internal dashboard, no deploy needed.

## Backend
**Goal:** issue company referral codes, attribute one company to another, and accrue profit-share
rewards into the referrer's reward wallet when the referee pays for a subscription — without being gameable.

### `referral_codes` + `referrals` migration — _ai-agent_
- **Goal:** a unique code **per company** and a record of each referrer-company → referee-company
relationship and its reward state.
- **Background:** the referral row tracks referrer company, referee company, status (`attributed` /
`rewarding` / `void`) and accrued reward total. **A referee company is attributed to at most one
referrer company**, fixed at signup and immutable thereafter.

### Code issuance + `POST /auth/register` capture — _engineer_
- **Goal:** generate a company's code and record the attribution when a new company registers with one.
- **Background:** extends the existing "approach A" pending-row registration. The referral code is
captured at register and bound when the pending row is promoted into the referee's **first
company** + owner membership. The code belongs to the company, not the user.

### Revenue-share accrual on referee subscription payment — _ai-agent_
- **Goal:** every time a referee company is charged for a subscription (initial **and** every
renewal), credit the referrer's **reward** wallet bucket with
`subscription_price × referral_percentage`.
- **Background:** fires off the **subscription-charge signal** from `subscription-plans` /
billing (the layer that knows the price and percentage — referral credits the amount handed to
it). Credits via the wallet's `Credit(..., reward, ...)` so the reward lands in the
**withdrawable** bucket (see `wallet-idr`). The `referral_percentage` is read from the referee's
**subscription plan** (see `subscription-plans`). Accrual is **recurring** (one credit per
subscription charge) and idempotent per charge.

### Anti-abuse guards — _ai-agent_
- **Goal:** stop self-referral and double-attribution.
- **Background:** block a company referring itself or another company under the same owner; one
referrer per referee; reuse registration's rate-limiting. Caps / eligibility configurable via the
internal dashboard.

## Frontend
**Goal:** let owners find, share, and track their company's referrals; let new companies enter a code.

### Referral page (Blade) — _engineer_
- **Goal:** show the company's code/share link, the companies it has referred, and reward earned.
- **Background:** calls the Go referral endpoints; earned rewards appear in the **reward** balance on
the wallet page (withdrawable / convertible there).

### Referral code field on registration — _engineer_
- **Goal:** capture an optional referral code at signup (and prefill it from a share link).
- **Background:** sits in the existing register flow; passes the code to `/auth/register`.

## Acceptance criteria
- [ ] Each company has a unique, shareable referral code/link.
- [ ] Signing up with a valid code attributes the new company to the referrer company; the attribution
is fixed and a company can be attributed to at most one referrer.
- [ ] On **every** subscription payment by a referred company (initial and each renewal), the
referrer's **reward** wallet is credited with the configured percentage of that subscription's price.
- [ ] No reward is paid on signup alone — only on a referee's subscription payment.
- [ ] Self-referral and attributing one referee to multiple referrers are rejected.
- [ ] Every referral reward appears in the referrer's wallet history as a **reward** (withdrawable) entry.
- [ ] The referral percentage is set per subscription plan (see `subscription-plans`) and is
configurable from the internal dashboard without a deploy.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/referral-program.md`. Postman: folder **Referral**
> + the `referral_code` field on **Auth › Register**.

### `GET /api/v1/referral` (JWT) — referral overview
`{ data: { code, share_url, total_reward, referrals: [ { referee_company_id, status, accrued_reward_total, created_at } ] } }`
(code issued lazily on first view). **401** without a valid JWT.

### `POST /api/v1/auth/register` — now accepts optional `referral_code`
Bad code → **422** `fields[referral_code]` (`validation.invalid_value`); without a code, registration succeeds.
Attribution happens on verify; rewards accrue to the referrer's **reward** wallet on the referee's
subscription payments (`referral_credit` entries).

## Implementation notes (done)
- Migration `00020`: `referral_code` (one per company, unique), `referral` (unique referee → one referrer,
  immutable, CHECK referrer≠referee), `referral_reward` (idempotent per-charge accrual ledger).
- `domain/referral` (Code/Referral, pure `RewardAmount` = price×pct/100 half-up, Repository with atomic `Accrue`).
- `usecase/referral.Service` is the register `RegistrationCodeValidator` + post-promotion `RegistrationListener`
  (attribution + anti-abuse) + subscription `ChargeListener` (recurring idempotent accrual → reward wallet) — unit-tested.
- `resource/postgres.ReferralRepository`; `handler.Referral` (GET /referral) + routes + main wiring
  (`SetReferralValidator`, `AddRegistrationListener`, `subscriptionService.AddChargeListener`).
- Reuses the register capture plumbing from the promo story. No new top-level apperr codes.
- Verified end-to-end vs the live DB: code issued; referee register+verify attributes; referee subscribe
  (99000 Pro @10%) credits the referrer reward wallet 9900.00 (idempotent ref); referral page shows it.

## Acceptance criteria
- [x] Each company has a unique, shareable referral code/link.
- [x] Signing up with a valid code attributes the new company to the referrer; fixed + at most one referrer per referee.
- [x] On every subscription payment by a referred company (initial + renewals), the referrer's reward wallet is credited the configured % of the price.
- [x] No reward on signup alone — only on a referee's subscription payment.
- [x] Self-referral and multi-referrer attribution are rejected.
- [x] Every referral reward appears in the referrer's wallet history as a reward (withdrawable) entry.
- [x] The referral percentage is set per subscription plan and configurable from the internal dashboard without a deploy.
- [x] Tests pass: `go build ./... && go test ./internal/...`.
