---
name: '[STORY] Omnichannel: unified agent inbox (control panel)'
priority: 3
status: done
tags:
    - omnichannel
    - inbox
    - backend
    - frontend
---

From one inbox in the control panel, an agent sees every conversation across all channels, opens a
thread, and replies — with the channel, AI/human status, and assignee all visible.

## Backend
**Goal:** the API endpoints the inbox needs — list/filter conversations, fetch a thread, send a reply,
assign, change status, and add internal notes.

### Conversation list + filters endpoint — _engineer_
- **Goal:** a paginated, tenant-scoped list with filters (channel, status, assignee, kind).
- **Background:** reads the conversation model, ordered by last activity. API-first so the panel and future
clients share it.

### Thread + reply endpoints — _engineer_
- **Goal:** fetch a conversation's messages and post an agent reply.
- **Background:** an agent reply is an outbound message authored by the user, routed via the outbound
dispatcher (`Dispatch`).

### Assignment, status & internal notes — _engineer_
- **Goal:** assign a conversation to an agent, change its status, and add internal-only notes.
- **Background:** internal notes are messages with internal direction — never delivered to the channel.
Status drives the inbox queues.

## Frontend
**Goal:** the inbox UI in the Laravel panel.

### Inbox list + thread view — _engineer_
- **Goal:** a conversation list (channel + status + assignee chips) beside a thread view with a reply
composer.
- **Background:** Blade + the shared Go API client; live updates come from the realtime story. The composer
is channel-aware (WhatsApp template hint, public-comment vs DM toggle for Instagram).

### Assignment + notes UI — _engineer_
- **Goal:** assign/take, change status, and write internal notes from the thread.
- **Background:** thin controls calling the assignment/status/notes endpoints.

## API contract — frontend handoff (build in parallel)

All routes under `/api/v1/inbox`, **Bearer JWT** (company + agent from the token). Full request/response
+ error bodies are in `documentation/features/omnichannel-agent-inbox.md` and Postman (folder
"Inbox (Omnichannel)"):
- `GET /inbox/conversations?channel=&status=&kind=&assignee=&limit=&offset=` → `{data:{conversations:[…]}}` (422 on bad filter)
- `GET /inbox/conversations/{id}` → conversation (404 `omnichannel.conversation_not_found`)
- `GET /inbox/conversations/{id}/messages?limit=&offset=` → `{data:{messages:[…]}}`
- `POST /inbox/conversations/{id}/reply` `{body,attachments?,reply_to_message_id?}` → 201 message (409 `omnichannel.send_window_closed`, 500 `omnichannel.channel_not_registered`)
- `POST /inbox/conversations/{id}/assign` `{assignee_user_id|null}` → 200 conversation
- `PATCH /inbox/conversations/{id}/status` `{status}` → 200 conversation (422 on bad status)
- `POST /inbox/conversations/{id}/notes` `{body}` → 201 internal note (`direction:internal`, never sent)

## Acceptance criteria
- [x] An agent sees all conversations across WhatsApp, Instagram, email, and web in one list, filterable
by channel/status/assignee. _(`GET /inbox/conversations` + `ConversationFilter`; ordered by last activity.)_
- [x] Opening a conversation shows the full message thread with each author and channel. _(`GET /inbox/conversations/{id}` + `/messages`; each message carries `participant_id` + the conversation's `channel`.)_
- [x] An agent can reply and the message is delivered over the conversation's channel. _(`POST …/reply` → `Dispatcher.Dispatch`; recorded with delivery status.)_
- [x] An agent can assign a conversation, change its status, and add internal notes that are never sent to
the customer. _(`…/assign`, `…/status`, `…/notes` — notes are `direction internal`, never dispatched.)_

### Verification (2026-06-10)
- `go build ./...`, `go vet ./...`, `go test ./...` — all pass. Inbox wired end-to-end in `cmd/api/main.go`
  (router group under `RequireJWT`); `make sqlc` regenerated (`ListConversations` with optional filters).
- Postman folder "Inbox (Omnichannel)" added with all 7 requests + success/error saved examples (JSON validated).
- Connector registry wired **empty** for now — external replies return `channel_not_registered` until a
  channel story registers its connector; internal conversations + all reads/assign/status/notes work today.
- `make lint` fails only on the pre-existing `golangci-lint`↔Go 1.25 toolchain bug (same on a clean tree).

> **Frontend (this repo) — done.** Implemented units:
> - **Inbox list + thread view**: split-pane layout (`inbox/index.blade.php`), conversation list with channel/status/assignee chips, filter dropdowns, thread partial with message bubbles (inbound/outbound/internal), reply composer with channel-aware hints.
> - **Assignment + notes UI**: take/unassign buttons, status dropdown, internal note toggle — all via vanilla JS fetch to JSON endpoints.
> - Files: `InboxApi.php`, `InboxController.php`, `inbox/index.blade.php`, `components/inbox/conversation-item.blade.php`, `inbox/partials/thread.blade.php`, sidebar nav item, layout `fullHeight` prop, routes, `app.js` initInbox, lang keys (EN + ID).
> - `php -l` all lang files — clean. `./vendor/bin/pint` — clean. `npm run build` — clean.
