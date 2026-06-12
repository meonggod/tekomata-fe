---
name: '[STORY] Promo code welcome credit at registration'
priority: 3
status: done
tags:
    - billing
    - promo
    - growth
    - wallet
---

A new company can enter a **promo code** when it registers and receive **welcome IDR credit** in
its **spendable** wallet, so it can try the product before topping up. Promo codes (amount,
validity window, usage cap) are managed in the tekomata-internal dashboard. This is distinct from
referral: promo credit is free spendable credit for the *new* company, not a referrer reward, and
it lands in the spendable bucket (usable in tekomata, not withdrawable).

## Backend
**Goal:** define redeemable promo codes and grant spendable wallet credit when a registering
company applies a valid one — once, without being gameable.

### `promo_codes` + `promo_redemptions` migration — _ai-agent_
- **Goal:** the catalogue of promo codes and a record of each redemption.
- **Background:** a promo code has a code, **credit amount (IDR)**, validity window, an overall
usage cap (and/or one-per-company), and an active flag — platform-level config. `promo_redemptions`
records which company redeemed which code and when, enforcing one redemption per company per code.

### Promo capture at `POST /auth/register` + grant — _ai-agent_
- **Goal:** capture an optional promo code at register and credit the new company's spendable wallet.
- **Background:** extends the "approach A" pending-row registration; the code is captured at
register and the grant fires when the pending row is promoted into the company + owner membership.
Credits via the wallet's `Credit(..., spendable, ...)` (see `wallet-idr`). Invalid / expired /
capped / already-redeemed codes are rejected with a clear reason and grant nothing. Grant is
idempotent so a replayed promotion can't double-credit.

### Promo code management endpoints — _engineer_
- **Goal:** tekomata-internal CRUD over promo codes.
- **Background:** tekomata-admin only; lets staff create campaigns (amount, window, cap) without a deploy.

## Frontend
**Goal:** let a registrant enter a promo code and let admins manage codes.

### Promo code field on registration — _engineer_
- **Goal:** capture an optional promo code at signup (prefillable from a link).
- **Background:** sits in the existing register flow alongside the referral code field; passes the
code to `/auth/register` and shows the granted credit (or the rejection reason) on success.

## Acceptance criteria
- [ ] A registering company can enter an optional promo code and, if valid, receives the configured
IDR credit in its **spendable** wallet (usable in tekomata, not withdrawable).
- [ ] Invalid, expired, used-up, or already-redeemed codes are rejected with a clear reason and grant
no credit; registration still succeeds without a code.
- [ ] A company can redeem a given promo code at most once; a replayed registration promotion does
not double-credit.
- [ ] Promo codes (amount, validity window, usage cap, active flag) are managed from the internal
dashboard without a deploy.
- [ ] The granted welcome credit appears in the company's wallet history as a spendable credit.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/promo-welcome-credit.md`. Postman: folder **Admin · Promo Codes**
> + the new `promo_code` field on **Auth › Register**.

### `POST /api/v1/auth/register` (public) — now accepts optional `promo_code`
Body adds `"promo_code": "WELCOME50"`. **202** generic (anti-enumeration). A bad/expired/fully-redeemed code
→ **422** with `fields[promo_code]` (`validation.invalid_value`); registration without a code always succeeds.
The credit is granted on verify and appears as a `promo_credit` spendable wallet entry.

### Platform-admin (X-Admin-Key) — promo CRUD
- `GET /api/v1/admin/promo-codes`
- `POST /api/v1/admin/promo-codes` `{ code, credit_amount, valid_from?, valid_until?, usage_cap?, is_active? }` → **201**; **409** `promo.code_taken`
- `PUT /api/v1/admin/promo-codes/{id}` → **200**
- `DELETE /api/v1/admin/promo-codes/{id}` → **204**

## Implementation notes (done)
- Migration `00019`: `promo_code` + `promo_redemption` (unique `(promo_code_id, company_id)`) +
  `pending_registration.{promo_code,referral_code}` capture columns (referral capture shared with the referral story).
- `domain/promo` (Code/Redemption, `IsRedeemable`, Repository with atomic `GrantToCompany`).
- `usecase/promo.Service` doubles as the register-time `account.RegistrationCodeValidator` AND the
  post-promotion `account.RegistrationListener` (grants spendable credit, idempotent) — unit-tested.
- `auth.Usecase` gained `SetPromoValidator` / `AddRegistrationListener`; Register validates+captures, Verify
  emits the `RegistrationEvent` to listeners (best-effort, never undoes a verified account).
- `resource/postgres.PromoRepository`; `handler.Promo` (admin) + register body fields; wallet `TxPromoCredit`.
- apperr: `promo.{not_found,code_taken}`. Config: none (uses `admin.api_key`).
- Verified end-to-end vs the live DB: invalid promo → 422; valid → 202 → verify → spendable +50000.00 as a
  `promo_credit` entry; `times_redeemed`→1; replay idempotent (unit test).

## Acceptance criteria
- [x] A registering company can enter an optional promo code and, if valid, receives the configured IDR credit in its **spendable** wallet.
- [x] Invalid/expired/used-up/already-redeemed codes are rejected with a clear reason and grant nothing; registration still succeeds without a code.
- [x] A company redeems a code at most once; a replayed promotion does not double-credit (unique index + idempotent wallet credit).
- [x] Promo codes (amount, window, cap, active) are managed from the internal dashboard without a deploy.
- [x] The granted welcome credit appears in the company's wallet history as a spendable credit (`promo_credit`).
- [x] Tests pass: `go build ./... && go test ./internal/...`.
