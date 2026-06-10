---
name: '[STORY] Omnichannel: real-time message delivery'
priority: 3
status: done
tags:
    - omnichannel
    - realtime
    - backend
    - frontend
---

New messages, typing indicators, and read receipts appear live in the agent inbox, the team chat, and
the website widget — no refresh needed.

## Backend
**Goal:** a realtime transport that pushes conversation events to connected clients, tenant-scoped.

### Realtime event hub — _engineer_
- **Goal:** push new-message / status / typing / read events to subscribers of a conversation or inbox.
- **Background:** WebSocket or SSE. Authorized by user (panel) or embed key (widget) and scoped to the
company. Fan-out happens on inbound ingest, outbound dispatch, and assignment/status changes.

### Read receipts + typing — _engineer_
- **Goal:** record and broadcast read state and typing.
- **Background:** a per-participant read marker on the conversation; typing is ephemeral (not persisted).

## Frontend
**Goal:** consume realtime events in the panel and the widget.

### Live inbox/thread updates — _engineer_
- **Goal:** subscribe and update the conversation list + the open thread in place.
- **Background:** shared by the agent inbox, team chat, and the web widget.

## Acceptance criteria
- [ ] A new inbound or outbound message appears in the open inbox/thread/widget without a reload.
- [ ] The conversation list reorders/updates live as activity arrives.
- [ ] Typing indicators and read receipts are shown where the channel/UI supports them.
- [ ] Realtime streams are scoped per company; a client only receives events for conversations it may see.

---
## API contract — frontend handoff
Full bodies: `documentation/features/omnichannel-realtime.md` + Postman (Inbox: Mark Read / Typing / Stream; Web Chat Widget: Stream).
- `GET /api/v1/inbox/stream` (Bearer JWT) — SSE of all company events (message|conversation|typing|read)
- `GET /api/v1/webhooks/web-chat/stream?site_key=&visitor_id=` (public) — SSE scoped to one visitor's conversation (404 if none yet)
- `POST /api/v1/inbox/conversations/{id}/read` → 204 (advances read marker, broadcasts `read`)
- `POST /api/v1/inbox/conversations/{id}/typing` → 204 (ephemeral, broadcasts `typing`)

## Acceptance criteria
- [x] A new inbound or outbound message appears in the open inbox/thread/widget without a reload. _(`Conversations.AppendMessage` publishes a `message` event to the hub → SSE; `TestConversationsPublishOnAppendAndStateChange`, hub fan-out tests.)_
- [x] The conversation list reorders/updates live as activity arrives. _(`message` carries `created_at`; `conversation` events carry `last_message_at`/`status`/`assignee`/`ai_active` on every state change.)_
- [x] Typing indicators and read receipts are shown where supported. _(`POST …/typing` broadcasts ephemeral `typing`; `POST …/read` advances `participant.last_read_at` + broadcasts `read`.)_
- [x] Realtime streams are scoped per company; a client only receives events for conversations it may see. _(hub filters by `company_id`; the widget stream is further scoped to the visitor's `conversation_id`; `TestHubCompanyScopedFanout`, `TestHubConversationScoped`.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass. `resource/realtime` hub suite covers company-scoped + conversation-scoped
  fan-out, unsubscribe, and non-blocking drop on a slow subscriber; `usecase/omnichannel` covers publish-on-
  append/state-change and nil-publisher safety. `make sqlc` regenerated (`participant.last_read_at`, migration `00013`).
- Transport: **SSE** (chosen default). The router exempts `*/stream` paths from the 60s request timeout so
  streams stay open; the in-memory hub is single-process (documented swap point: Redis/NATS behind the same
  `EventPublisher`/`EventStream` ports). Wired in `cmd/api/main.go` (`SetPublisher` on the model service).
- `EventSource` can't send an Authorization header — the panel must stream same-origin (cookie/proxy); noted in the feature doc.
- `make lint` fails only on the pre-existing `golangci-lint`↔Go 1.25 toolchain bug.
- (Frontend live-update wiring is Laravel — out of scope.)
