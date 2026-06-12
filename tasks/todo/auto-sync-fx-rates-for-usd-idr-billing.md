---
name: '[STORY] Auto-sync FX rates for USD→IDR billing'
priority: 2
status: done
tags:
    - billing
    - currency
    - fx
---

AI provider costs are incurred in **USD**, but companies are billed and hold their wallet in
**IDR**. This story keeps exchange rates fresh automatically so every AI cost can be converted
to IDR at a recent, auditable rate before it's charged, and so prices can be shown in a
company's enabled currencies. It is the conversion half of the billing work the
`currency-catalog` story deliberately deferred.

## Backend
**Goal:** maintain recent FX rates from a provider on a schedule, expose a conversion function,
and refuse to bill on stale rates.

### `exchange_rates` migration — _ai-agent_
- **Goal:** store the latest rate per currency pair with the time it was fetched.
- **Background:** builds on the `currencies` table from `currency-catalog`. Keep rate history
(append rows) so a past charge can be explained by the rate in force at the time, not just the
current one.

### FX provider adapter — _engineer_
- **Goal:** fetch current rates from an external FX source.
- **Background:** hand-rolled `net/http` adapter, no SDK, like the repo's other clients.
Credentials are tekomata-owned (config / `${ENV_VAR}`), never per-company.

### Scheduled rate sync + manual re-sync endpoint — _engineer_
- **Goal:** refresh rates on a cadence and let tekomata force a refresh.
- **Background:** periodic job (e.g. daily) writes new rate rows; a tekomata-internal endpoint
triggers an on-demand sync. Pairs with the dashboard family (`subscription-tiers`,
`ai-provider-registry`). A failed sync alerts via the existing `error-alerting` path and
leaves the last good rate in place.

### `func Convert(amount, from, to) (Money, error)` + staleness guard — _ai-agent_
- **Goal:** centralize conversion so the charging engine stays thin, and never silently use a
stale rate.
- **Background:** uses the latest rate for the pair; returns an error (so the caller can stop
rather than mischarge) when the freshest rate is older than a configured max age. This is the
function `billing-charging-engine` calls to turn USD AI cost into IDR.

## Frontend
**Goal:** let tekomata see and control rate freshness from the internal dashboard.

### Internal FX rates view (Blade) — _engineer_
- **Goal:** show current rates, last-synced time, and a "sync now" action.
- **Background:** tekomata-internal panel, same family as the pricing dashboard; calls the Go
rates + manual-sync endpoints.

## Acceptance criteria
- [ ] FX rates refresh automatically on a schedule without a deploy, and tekomata can trigger a sync manually.
- [ ] `Convert` returns the IDR amount for a given USD amount using the most recent rate.
- [ ] Conversion refuses (returns an error) when the freshest rate is older than the configured max age.
- [ ] A past charge can be explained by the rate that was in force when it happened.
- [ ] A failed sync raises an alert and leaves the last good rate in place rather than breaking billing.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/fx-rate-sync.md`. Postman: folder **Admin · FX**.
> These are **tekomata-internal** (platform-admin) endpoints — NOT tenant-scoped. They require the
> `X-Admin-Key` header and are mounted only when `admin.api_key` is configured. `Convert` itself is an
> in-process function (`usecase/fx.Service.Convert`) the charging engine calls — no public endpoint.

### `GET /api/v1/admin/fx/rates` — current rates + freshness (platform-admin)
**200**
```json
{ "data": { "rates": [
  { "base_code": "USD", "quote_code": "IDR", "rate": "17945.22141600",
    "source": "open.er-api.com", "fetched_at": 1781260621059, "stale": false }
] } }
```
**401** (missing/invalid `X-Admin-Key`)
```json
{ "error": { "code": "unauthorized", "message": "authentication required", "request_id": "…" } }
```

### `POST /api/v1/admin/fx/sync` — force an immediate refresh (platform-admin)
**200**
```json
{ "data": { "synced": true, "rates": [
  { "base_code": "USD", "quote_code": "IDR", "rate": "17945.22141600",
    "source": "open.er-api.com", "fetched_at": 1781260621059, "stale": false }
] } }
```
**500** (provider not configured — `fx.enabled=false`)
```json
{ "error": { "code": "fx.sync_unavailable", "message": "the exchange-rate provider is not configured", "request_id": "…" } }
```

### Conversion errors (raised by callers of `Convert`, e.g. the charging engine)
`fx.rate_stale` (409) — freshest rate older than `fx.max_age_hours`; `fx.rate_unavailable` (409) — no rate row yet for the pair.

## Implementation notes (done)
- Migration `00017_exchange_rate` (append-only rate history; `idx_exchange_rate_pair`).
- `domain/fx` (Rate, Repository/Provider ports, pure `Convert` with half-up rounding via math/big).
- `usecase/fx.Service` (Sync, Convert + staleness guard, LatestRates, RunScheduler) — unit-tested.
- `resource/fx` (open.er-api.com net/http adapter + `Disabled` fallback); `resource/postgres.FXRepository`.
- `handler.FX` + platform-admin middleware (`RequirePlatformAdmin`, `X-Admin-Key`); wired in `main.go`
  (scheduler via `safego`, syncs on boot + every `fx.sync_interval_hours`).
- Config: `fx.*` + `admin.api_key`. apperr: `fx.{rate_stale,rate_unavailable,sync_unavailable}`.
- Verified end-to-end against the live DB: boot sync fetched a live USD→IDR rate; GET/POST admin endpoints
  return correct bodies; 401 without the admin key.

## Acceptance criteria
- [x] FX rates refresh automatically on a schedule without a deploy, and tekomata can trigger a sync manually.
- [x] `Convert` returns the IDR amount for a given USD amount using the most recent rate.
- [x] Conversion refuses (returns an error) when the freshest rate is older than the configured max age.
- [x] A past charge can be explained by the rate that was in force when it happened (append-only history).
- [x] A failed sync raises an alert and leaves the last good rate in place rather than breaking billing.
- [x] Tests pass: `go build ./... && go test ./internal/...` (lint toolchain known-broken; build/vet/test green).
