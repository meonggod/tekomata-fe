---
name: '[STORY] Prepaid IDR wallet — spendable + withdrawable reward buckets'
priority: 2
status: doing
tags:
    - billing
    - wallet
    - payments
---

Every company holds **two IDR balances**: a **spendable balance** it tops up and that every
billable action draws down, and a **withdrawable reward balance** filled only by referral
earnings. Top-up money is spendable but **not** withdrawable; reward money is withdrawable to a
bank but **cannot pay for anything in tekomata** until the company **converts** it into the
spendable balance. The owner sees both balances and a full history, and the assistant stops
answering (with a clear "top up to continue" message) once the **spendable** balance can't cover
the next query. This is the money container the rest of the billing layer debits against.

## Backend
**Goal:** hold two ledgered IDR buckets per company — **spendable** and **reward** — with atomic
credit/debit/convert, a withdraw-to-bank flow for reward funds, and a sufficient-funds gate over
the **spendable** bucket only.

### `wallets` + `wallet_transactions` migration — _ai-agent_
- **Goal:** one wallet row per company carrying **two** balances (spendable + reward), plus an
append-only transaction ledger.
- **Background:** company is the tenant (many-to-many user↔company). Each ledger entry records the
**bucket** (`spendable` / `reward`), type (`topup` / `debit` / `referral-credit` / `convert-out`
/ `convert-in` / `withdrawal` / `refund` / `adjustment`), signed IDR amount, that bucket's
running balance, source reference, and timestamp. **Each bucket's balance is always derivable
from its entries**; store both denormalized for fast reads but never let either drift.

### `func Credit` / `func Debit` — _ai-agent_
- **Goal:** the controlled ways money moves; both atomic and ledgered.
- **Background:** `Credit(companyID, amountIDR, bucket, reason, ref)` targets a named bucket —
top-ups credit **spendable**, referral rewards credit **reward**. `Debit(companyID, amountIDR,
reason, ref)` **only ever debits the spendable bucket** (all tekomata charges — usage,
subscription, paid features — go through here). Reward funds can never be debited for a charge.
A `Debit` settling a cost tekomata has **already incurred** records even if it pushes spendable
slightly negative — exposure bounded to that one event; the `HasBalanceFor` gate stops
unaffordable work from starting. A **refund** reverses a prior charge via `Credit` to spendable
carrying that charge's reference.

### `func Convert(companyID, amountIDR)` — _ai-agent_
- **Goal:** the **only** way reward money becomes usable in tekomata — move reward → spendable.
- **Background:** atomically writes a `convert-out` on the reward bucket and a `convert-in` on the
spendable bucket. Requires sufficient reward balance. Once converted, the funds are spendable and
**no longer withdrawable**. This is what enforces "reward can't pay for anything until converted."

### `func HasBalanceFor(companyID, amountIDR) bool` — _ai-agent_
- **Goal:** cheap pre-check the assistant/lookup path calls before doing paid work.
- **Background:** checks the **spendable** bucket only (reward funds don't count). Ties into
`usage-meter` / `assistant` so a query is refused before AI cost is incurred when spendable can't
cover base rate + estimated AI cost; also refuses while spendable is non-positive.

### `POST /companies/{id}/wallet/topup` + payment webhook — _engineer_
- **Goal:** let a company add IDR (to **spendable**) via an Indonesian payment provider and credit
on confirmation.
- **Background:** hand-rolled `net/http` adapter (Xendit/Midtrans-style), no SDK. The endpoint
creates a top-up intent; the provider webhook is the source of truth that calls `Credit(...,
spendable, ...)`. Idempotent on the provider reference so a replay can't double-credit.

### `POST /companies/{id}/wallet/convert` — _engineer_
- **Goal:** let an owner move reward funds into spendable so they can be used.
- **Background:** thin handler over `Convert`; tenant-scoped; rejects amounts above the reward
balance.

### `POST /companies/{id}/wallet/withdraw` + payout webhook — _engineer_
- **Goal:** let a company cash out **reward** funds to a verified bank account.
- **Background:** uses the provider's **disbursement/payout** API (same hand-rolled adapter);
withdrawal debits the **reward** bucket only — spendable/top-up funds can never be withdrawn.
Webhook-confirmed and idempotent on the payout reference; requires KYB + a verified bank account
(see `post-login-kyc-kyb-onboarding`). Minimum amount / fee configurable in the internal dashboard.

### `GET /companies/{id}/wallet` — _engineer_
- **Goal:** return both balances + recent transactions for the panel.
- **Background:** tenant-scoped via auth middleware; also reachable with a per-company API key.

## Frontend
**Goal:** a wallet page where the owner sees both balances, tops up, converts, withdraws, and reviews history.

### Wallet page (Blade) — _engineer_
- **Goal:** show spendable + reward balances, a top-up flow, a reward→spendable convert action, a
withdraw-to-bank action, and a paginated transaction list tagged by bucket.
- **Background:** calls the Go wallet endpoints; surfaces a low-spendable-balance warning. Top-up
and withdraw redirect to / poll the payment provider and show the result on return; withdraw is
gated on KYB + bank verification.

## Acceptance criteria
- [ ] A company has exactly one wallet with two balances; each balance always equals the sum of its
own ledger entries.
- [ ] A confirmed top-up credits the **spendable** balance; a replayed payment webhook does not double-credit.
- [ ] Referral rewards credit the **reward** balance only (see `referral-system`).
- [ ] Charges (usage, subscription, features) debit the **spendable** balance only — reward funds can
never be spent inside tekomata.
- [ ] Reward funds cannot pay for anything until converted: a company can convert reward → spendable,
and converted funds become spendable and are no longer withdrawable.
- [ ] A company can withdraw **reward** funds to a verified bank account (KYB + bank required); a
replayed payout webhook does not double-pay. Spendable/top-up funds cannot be withdrawn.
- [ ] New paid work is gated on the **spendable** balance: when it can't cover the next query (base
rate + estimated AI cost), the assistant declines and tells the owner to top up.
- [ ] Concurrent debits cannot double-spend and cannot drive spendable negative beyond a single
in-flight settlement.
- [ ] The owner can view both balances and a full, bucket-tagged history in the panel.

---

## API contract — frontend handoff (build in parallel)

All JWT-only, tenant-scoped: the path company id MUST equal the active company (403 otherwise). Money is a
decimal string (IDR). Full contract + Mermaid in `documentation/features/prepaid-idr-wallet.md`; Postman
folder *Wallet*.

| Method | Path | Purpose | Success |
|--------|------|---------|---------|
| GET | `/api/v1/companies/{id}/wallet?limit=&offset=` | balances + history | `200 {spendable_balance, reward_balance, transactions[]}` |
| POST | `/api/v1/companies/{id}/wallet/topup` `{amount}` | add spendable (provider checkout) | `201 {topup_id, payment_url, status}` |
| POST | `/api/v1/companies/{id}/wallet/convert` `{amount}` | reward → spendable | `200 {spendable_balance, reward_balance}` |
| POST | `/api/v1/companies/{id}/wallet/withdraw` `{amount, bank_code, account_number, account_holder}` | reward → bank (KYB gated) | `201 {withdrawal_id, status}` |
| POST | `/api/v1/webhooks/payment` | public; provider confirms top-up (`X-Callback-Token`) | `200` |
| POST | `/api/v1/webhooks/payout` | public; provider settles payout | `200` |

Errors: `wallet.invalid_amount` (422), `wallet.insufficient_reward` (409), `wallet.withdraw_not_allowed`
(403), `wallet.payment_unavailable` (500).

## Implementation notes (done)
- Migration `00016` (`wallet`, `wallet_transaction`, `wallet_topup`, `wallet_withdrawal`); `db/queries/wallet.sql`; sqlc. ERD updated.
- Layers: `domain/wallet` (entities/enums + `Repository`/`PaymentProvider` ports), `usecase/wallet.Service`
  (the money API: `Credit`/`Debit`/`Convert`/`HasBalanceFor` + topup/withdraw/webhook orchestration),
  `resource/postgres/wallet_repo.go` (atomic two-bucket ledger — balance UPDATE row-locks + same-tx ledger
  insert; idempotent on `(type, reference)`), `resource/payment` (hand-rolled net/http PSP adapter +
  `Disabled` fallback), `resource/http/handler/wallet.go`, routes + `main.go` wiring, `config.Payment`.
- `Debit` is spendable-only and always records (gate is `HasBalanceFor`); `Convert`/`withdraw` reject on
  short reward; webhooks idempotent. Money math is in SQL (`NUMERIC`), never a float.
- Tests: `usecase/wallet/service_test.go` (validation, provider gating, KYB gate, payout-fail refund,
  webhook paid-filtering). `go build/vet/test ./...` green; migration applied locally (v16). The
  `wallet.Service` is ready for the subscription/charging/referral/promo tasks to debit/credit.
- **Deferred:** real bank-account verification (gated on `kyb_profile.status=complete` + bank details on
  the request). The PSP adapter is Xendit-shaped but untested against a live provider.
