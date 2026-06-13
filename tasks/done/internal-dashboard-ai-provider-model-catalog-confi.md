---
name: '[STORY] Internal dashboard — AI provider & model catalog config'
priority: 3
status: done
tags:
    - internal-dashboard
    - admin
    - ai
    - config
---

tekomata staff manage the AI provider platform from `/internal/*` — providers, credentials, the model
catalog and its pricing/flags, and the auto-sync pending-review queue — the config the AI provider
epic's cards said is "managed in the tekomata-internal dashboard with no deploy." This story delivers
that panel and its admin endpoints. **Depends on `internal-dashboard-foundation`** (staff guard, audit
log, console shell).

## Backend
**Goal:** expose the AI provider & model-catalog admin endpoints behind the foundation's staff guard
and audit log.

### Mount/expose the AI provider & catalog config endpoints — _engineer_
- **Goal:** a guarded home for the provider registry + model catalog config (`ai-provider-registry`,
`ai-model-catalog-sync`), adding any thin admin endpoints the cards assumed but never exposed.
- **Background:** route under `/internal/*` behind the foundation guard and audit log, covering:
**providers + credentials** (tekomata-owned, never per-company) and **priority/fallback order**;
**per-model** pricing, `is_active` (platform-level), and `user_selectable`; the per-company
**`allow_model_choice`** flag; the **pending-review queue** of auto-discovered models (price +
activate — never bill a tenant on an unpriced model); and a **manual sync** trigger exposing run
summaries (added/removed/unchanged) and failures. DB-backed, no deploy.

## Frontend
**Goal:** the AI providers & model-catalog panel, plugged into the console shell.

### AI providers & model catalog panel (Blade) — _engineer_
- **Goal:** screens to manage providers and the model catalog, each reading/writing the Go endpoints.
- **Background:** plugs into the foundation's role-gated nav. Surfaces:
- **Providers** — credentials, priority / fallback order.
- **Model catalog** — per-model pricing, `is_active`, `user_selectable`, per-company `allow_model_choice`.
- **Pending-review queue** — auto-discovered models awaiting a price; price + activate from here.
- **Manual sync** — trigger a catalog sync; show run summaries and surfaced failures.

## Acceptance criteria (backend)
- [x] All endpoints sit behind the foundation's `/internal/*` staff guard; reads any staff, writes
superadmin-only (`RequireStaffMutate`). *(verified)*
- [x] Staff manage providers (credentials write-only — reads show `has_credential`, never the secret;
priority/fallback) and per-model pricing / `is_active` / `user_selectable` / per-company `allow_model_choice`,
all DB-backed, no deploy. *(verified: provider create→list (no leak), model price+activate, ai-preference set/get)*
- [x] Models land in a **pending-review queue** (`status=pending`, `priced=false`) and can be priced +
activated (`POST /ai/models/{id}/price`); **an unpriced model is never billable** (`priced`/`is_active`
exposed for the charging engine to gate on). *(verified)*
- [x] Staff trigger a **manual catalog sync** (`POST /ai/sync`) and see its summary
`{added,removed,unchanged,failures}`. *(verified — sync is a **stub** until upstream auto-discovery lands)*
- [x] Every config write is recorded in the foundation's audit log (`AuditConfigChanges`).
- [x] *(Frontend — Laravel)* The providers/catalog panel UI. Built in the FE repo.

> **Deferred:** sync auto-discovery (no upstream model-list integration yet — the panel + summary are
> wired; discovered models will insert as pending) and `kind=ai` line-item billing on the catalog.

## API contract — frontend handoff (build in parallel)
See **`documentation/features/internal-dashboard-ai-config.md`** (full contract + Mermaid + examples).
Postman: **Internal · AI Catalog** folder. Migration `00029` adds `ai_provider` + `ai_model` +
`company_ai_preference`.

**Status:** done
