---
name: '[STORY] Internal dashboard — CS-assistant review queue'
priority: 3
status: done
tags:
    - internal-dashboard
    - admin
    - cs
---

tekomata staff review the questions the CS feature-assistant couldn't answer and grow its knowledge —
the future surface `cs-feature-assistant` explicitly named ("a future tekomata-internal dashboard
where admins review unanswered / low-confidence questions and add knowledge"). The assistant already
captures every question with an answered/low-confidence flag; this story turns that captured stream
into an actionable review queue. **Depends on `internal-dashboard-foundation`** (staff guard, audit
log, console shell).

## Backend
**Goal:** expose the review-queue and knowledge endpoints behind the foundation's staff guard and audit log.

### CS review + knowledge endpoints — _engineer_
- **Goal:** list the questions worth reviewing and let staff add the knowledge that answers them.
- **Background:** read the unanswered / low-confidence questions `cs-feature-assistant` records (with
surface — homepage vs in-app — and the user/company when authenticated), let staff mark one
reviewed, and create/edit entries in the assistant's **platform-level** feature/pricing knowledge
(about tekomata's own product, not per-company). Routed under `/internal/*` behind the foundation
guard; knowledge edits write through the audit log.

## Frontend
**Goal:** the CS-assistant review panel, plugged into the console shell.

### CS-assistant review panel (Blade) — _engineer_
- **Goal:** a queue of unanswered / low-confidence questions and an add-knowledge action.
- **Background:** plugs into the foundation's role-gated nav; shows each question with its surface and
(when present) the asker, lets staff add/edit knowledge and mark the item reviewed.

## Acceptance criteria (backend)
- [x] All endpoints sit behind the foundation's `/internal/*` staff guard and reject non-staff.
*(verified: `GET /internal/cs-questions` 200 with staff token; tenant token already rejected by the guard)*
- [x] Staff see a queue of unanswered / low-confidence (below `ai.confidence_threshold`) **not-yet-reviewed**
questions (`GET /internal/cs-questions?status=needs_review`), each with `surface` + (when authenticated) the
asking `user_id`/`company_id`.
- [x] Staff can add/edit the platform KB (`/internal/knowledge-entries`) and **mark a question reviewed**
(`POST /internal/cs-questions/{id}/review` → `204`; unknown id → `404`). Operational (non-money) → any staff incl. `ops`.
- [x] Knowledge edits + reviews are recorded in the foundation's audit log (via `AuditConfigChanges`).
- [x] *(Frontend — Laravel)* The review panel UI. Built in the FE repo.

## API contract — frontend handoff (build in parallel)
See **`documentation/features/internal-dashboard-cs-review.md`** (full contract + Mermaid + examples).
Postman: **Internal · Knowledge Base** folder (Review Queue + Mark Question Reviewed). Migration `00027`
adds `cs_question.reviewed_at`.

**Status:** done
