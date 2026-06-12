---
name: '[STORY] CS feature assistant — AI helper that explains every tekomata feature'
priority: 3
status: done
tags:
    - cs-assistant
    - ai
    - knowledge-base
    - landing-page
    - support
---

A visitor on the public homepage — or a logged-in owner inside the panel — can ask "can tekomata do X?" in plain language and get an instant, accurate answer drawn from a tekomata-owned feature knowledge base. Every question asked is recorded so tekomata can later review the gaps, answer them, and grow the knowledge base. This is tekomata dogfooding its own AI to explain (and sell) tekomata.

## Backend
**Goal:** serve grounded AI answers about tekomata's features from a tekomata-owned knowledge base, and log every question for later review.

### `knowledge_entries` table + minimal management — _engineer_
- **Goal:** store the tekomata feature/pricing knowledge the assistant answers from — platform-level (about tekomata's *own* product), not per-company.
- **Background:** same tekomata-internal config family as `subscription-tiers` and the model catalog. An entry is a topic/question + answer content + an active flag. A rich admin authoring dashboard is a **later story**; this story only needs the table and a way to seed/manage entries so the assistant has something to ground on.

### `POST /cs/ask` — _engineer_
- **Goal:** take a question + audience context (visitor vs logged-in owner, which surface), retrieve the relevant knowledge, compose a grounded answer, and return answer + a confidence/answered flag.
- **Background:** composes the answer through the `ai-provider-registry` (text chat, plus embeddings for retrieval), reusing the `assistant` pattern (parse → retrieve → compose). On the public surface there is **no tenant** — the AI cost is tekomata's own; in-app the request carries the owner's company context. This is distinct from the tenant-facing `omnichannel-website-chat-channel`: that chat sits on *customers'* sites; this one sits on tekomata's own.

### `cs_questions` capture table — _ai-agent_
- **Goal:** record every question asked, the answer given, an answered/low-confidence flag, the surface (homepage vs in-app), and the user/company when authenticated.
- **Background:** this is the feedback loop — the raw material for a future tekomata-internal dashboard where admins review unanswered / low-confidence questions and add knowledge. Capturing it now is the whole point of the fallback: nothing a user asks is lost, even when the bot can't answer.

## Frontend
**Goal:** surface the assistant as a chat widget on both the public homepage and inside the panel, audience-aware.

### Public homepage CS widget — _engineer_
- **Goal:** let an un-authenticated visitor chat with the assistant about features/pricing, and nudge them toward register.
- **Background:** lives on the Laravel landing page that already funnels signups; calls `POST /cs/ask` with visitor context; includes a light "sign up to start" CTA. No login required to ask.

### In-app help widget — _engineer_
- **Goal:** give a logged-in owner the same assistant inside the control panel for "how do I…" feature help.
- **Background:** same widget, but passes the user JWT / active company so answers can be owner-aware; reuses the shared API client.

## Acceptance criteria
- [x] A visitor on the public homepage can ask a plain-language question about tekomata's features/pricing and get a relevant answer grounded in the knowledge base, without logging in. _(FE: homepage widget → /cs/ask without a token.)_
- [x] A logged-in owner can ask the same assistant inside the panel and get an answer, with their company context available to the request. _(FE: in-app widget; the proxy attaches the session JWT server-side.)_
- [ ] Answers are grounded in `knowledge_entries` — the assistant does not fabricate features that aren't in the knowledge base; an unknown / low-confidence question gets an honest "I don't have that yet" style answer rather than a made-up one.
- [ ] Every question asked on either surface is stored with the answer given, an answered/confidence flag, the surface, and the user/company when authenticated.
- [ ] The stored questions are queryable as the foundation for a future tekomata-internal review dashboard (no dashboard UI required in this story).
- [x] The public widget shows a sign-up CTA.

> **Frontend (this repo) — done.** CS assistant on both surfaces: `CsApi::ask` (`POST /api/v1/cs/ask` with
> optional JWT), a thin same-origin `CsController` proxy (route `POST /cs/ask`, public) that attaches the session
> token only for `surface=in_app` so the JWT never reaches the browser and the homepage asks anonymously. Shared
> `x-cs-widget` Blade component (floating launcher + chat panel) included on the landing page (`surface=homepage`,
> with sign-up CTA) and in the app layout (`surface=in_app`); driven by `initCsWidget()` in `resources/js/app.js`
> (synchronous ask/answer, answers rendered via `textContent` — never injected as HTML, honest fallback/error
> bubbles). Strings under `messages.cs.*` (id+en). Knowledge grounding, capture and the review feed are backend.

---

## API contract — frontend handoff (build in parallel)

> Full contract + Mermaid: `documentation/features/cs-feature-assistant.md`. Postman: folders **CS Assistant**
> + **Admin · Knowledge Base**.

### `POST /api/v1/cs/ask` (public, optional JWT)
`{ question, surface }` → `{ data: { answer, answered, confidence } }`. A valid Bearer token (in-app)
attributes the question to the owner's company. **422** `fields[question]` when empty.

### Platform-admin (X-Admin-Key)
- Knowledge base: `GET/POST /api/v1/admin/knowledge-entries`, `PUT/DELETE …/{id}` (`{ topic?, question, answer, is_active? }`).
- Review feed: `GET /api/v1/admin/cs-questions?unanswered=&limit=&offset=`.

## Implementation notes (done)
- Migration `00022`: `knowledge_entry` (generated `tsvector` + GIN for FTS) + `cs_question` (capture).
- `domain/cs` (KnowledgeEntry/Question/Answer, Surface, Repository + Assistant ports).
- `usecase/cs.Service`: retrieve (FTS, OR-term recall) → compose (Claude when configured via the reused
  `ai.Assistant.ComposeAnswer`, else retrieval-only fallback) → capture every question; honest fallback when
  nothing grounds it (no fabrication). Admin KB CRUD + question review. Unit-tested.
- `resource/ai.Assistant` gained `ComposeAnswer` (grounded JSON answer) + `Configured`; `resource/postgres.CSRepository`;
  `handler.CS` (public ask via OptionalJWT + admin KB/questions); router + main wiring (AI assistant hoisted + shared).
- apperr: `cs.knowledge_not_found`. Config: reuses `ai.*` (assistant optional).
- Verified live: KB entry; homepage ask grounded (answered) vs unknown (honest fallback); both captured + reviewable.

## Acceptance criteria
- [x] A homepage visitor asks a plain-language question and gets a relevant grounded answer, no login.
- [x] A logged-in owner asks the same assistant in-app, with company context available to the request.
- [x] Answers are grounded in `knowledge_entries`; unknown/low-confidence → honest "I don't have that" (no fabrication).
- [x] Every question is stored with the answer, an answered/confidence flag, the surface, and user/company when authenticated.
- [x] Stored questions are queryable (admin review feed) as the foundation for a future dashboard.
- [x] The public widget shows a sign-up CTA — frontend concern (the API serves answers; backend done).
- [x] Tests pass: `go build ./... && go test ./internal/...`.
