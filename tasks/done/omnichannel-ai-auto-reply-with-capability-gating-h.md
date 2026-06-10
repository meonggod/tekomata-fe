---
name: '[STORY] Omnichannel: AI auto-reply with capability gating + human handoff'
priority: 3
status: done
tags:
    - omnichannel
    - ai
    - backend
    - frontend
---

The AI answers omnichannel conversations automatically using the catalog assistant, and when it can't
help — low confidence or out of scope — it escalates to a human who can take over and later hand the
conversation back to the AI.

## Backend
**Goal:** drive replies through the existing assistant, gate on capability/confidence, and manage the
AI⇄human handoff state on a conversation.

### AI reply driver — _engineer_
- **Goal:** when a conversation is AI-active, generate a reply via the existing `assistant` + `lookup-core`
and dispatch it.
- **Background:** reuses the assistant (parse → lookup → compose) but channel-agnostic via the outbound
dispatcher. Each AI answer is a billable usage event (base rate + AI cost), exactly like today.

### Capability/confidence gate + escalation — _engineer_
- **Goal:** decide when the AI should NOT answer, and escalate instead of guessing.
- **Background:** signals = low confidence, no catalog match, an explicit "talk to a human", or repeated
failure. Escalation flips the conversation to human-owned, marks it pending, and assigns/notifies.

### Handoff state machine — _engineer_
- **Goal:** manage transitions AI-active → human → back-to-AI, with a clear owner at every moment.
- **Background:** a human taking over silences the AI for that conversation; an agent can return it to the
AI. State lives on the conversation (`ai_active` / `assignee` from the model story).

## Frontend
**Goal:** let an agent see AI status and take over / hand back from the inbox.

### AI status + takeover controls — _engineer_
- **Goal:** show whether the AI is handling a conversation and offer take-over / return-to-AI actions.
- **Background:** sits in the agent-inbox thread view; calls the handoff endpoints.

## Acceptance criteria
- [ ] In an AI-active conversation, an inbound message gets an automatic AI reply via the catalog
assistant.
- [ ] Each AI answer records a billable usage event (base rate + AI cost).
- [ ] When the AI is low-confidence / out of scope / asked for a human, the conversation escalates and is
marked for a human.
- [ ] An agent can take over (the AI goes silent) and later hand back to the AI; the conversation always
has a clear current owner.

---
## API contract — frontend handoff
The AI reply itself is produced server-side from inbound webhooks (no client trigger). Handoff endpoints
(Bearer JWT) — full bodies in `documentation/features/omnichannel-ai-auto-reply.md` + Postman "Inbox":
- `POST /api/v1/inbox/conversations/{id}/takeover` → 200 conversation (`ai_active:false`, assigned to you, reopened)
- `POST /api/v1/inbox/conversations/{id}/handback` → 200 conversation (`ai_active:true`, unassigned)

## Acceptance criteria
- [x] In an AI-active conversation, an inbound message gets an automatic AI reply via the assistant. _(`Ingestor.SetInboundHook` → async `AutoReplier.AutoReply` → Claude API draft → `Dispatcher.Dispatch`; `TestAutoReplySendsHighConfidence`.)_
- [x] Each AI answer records a billable usage event (base rate + AI cost). _(`ai_usage_event` ledger via `UsageRecorder`; `kind` ai_reply with model + token counts; escalations recorded too.)_
- [x] When the AI is low-confidence / out of scope / asked for a human, the conversation escalates and is marked for a human. _(gate → `SetAIActive(false)` + `status=pending` + internal note; `TestAutoReplyEscalatesLowConfidence`, `TestAutoReplyEscalatesOnHumanRequestWithoutCallingModel`.)_
- [x] An agent can take over (AI goes silent) and later hand back; the conversation always has a clear owner. _(`/takeover` sets `ai_active=false`+assignee+open; `/handback` sets `ai_active=true`+unassign.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass. `usecase/omnichannel` AutoReplier suite covers send (high confidence),
  escalate (low confidence), escalate-on-human-request (no model call), and AI-inactive no-op against a fake
  assistant + fake usage recorder. `resource/ai` covers the strict-JSON parse. `make sqlc` regenerated
  (`ai_usage_event`). Official `anthropic-sdk-go` added; default model `claude-opus-4-8` (configurable).
- Wired in `cmd/api/main.go` when `ai.api_key` is set: the inbound hook runs `AutoReply` via `safego` on the
  app context (outlives the webhook). Migration `00012` + ERD updated. `make lint` fails only on the
  pre-existing toolchain bug.
- Model choice: defaulted to `claude-opus-4-8` per the Claude API guidance, configurable to a cheaper tier
  (e.g. `claude-haiku-4-5`) for high-volume support — documented in the feature doc + env.example.
- (Frontend AI-status/takeover UI is Laravel — out of scope.)

> **Frontend (this repo) — done.** Implemented "AI status + takeover controls":
> `InboxApi::takeover/handback`, `InboxController::takeover/handback`, routes, EN/ID lang keys,
> data-attributes on inbox root, `renderThread()` AI-active/human-active chip + Take over / Hand
> back button + delegated click handlers. `php -l` clean, Pint passed, `npm run build` OK.
