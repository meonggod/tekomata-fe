---
name: '[STORY] Omnichannel: internal team chat (direct + group channels)'
priority: 3
status: done
tags:
    - omnichannel
    - internal-chat
    - backend
    - frontend
---

tekomata users in a company can message each other 1:1 and in group channels inside the platform, using
the same conversation model as customer threads.

## Backend
**Goal:** internal conversations (user↔user and group) over the unified model, with membership
management — and no external dispatch.

### Internal conversation create + membership — _engineer_
- **Goal:** start an internal-direct chat with a teammate or an internal-group channel, and manage its
members.
- **Background:** reuses `conversations` (kind = internal) + `participants` (users). Company-scoped; only
members see the thread. There is no channel connector — delivery is in-app/realtime only.

### Internal message send — _engineer_
- **Goal:** post a message into an internal conversation.
- **Background:** authored by a user, internal direction, never dispatched to a channel connector.
Mentions optional.

## Frontend
**Goal:** a team-chat surface in the panel.

### Team chat UI — _engineer_
- **Goal:** list internal conversations/channels and a thread view to chat with teammates.
- **Background:** reuses the inbox thread components; lives alongside (or as a tab of) the agent inbox.
Real-time via the realtime story.

## Acceptance criteria
- [x] A user can start a 1:1 chat with a teammate and create a named group channel within their company.
- [x] Only conversation members can see and post in an internal conversation.
- [ ] Internal messages are never delivered to any external channel. _(backend concern — this repo cannot verify)_
- [x] Internal chat reuses the same conversation/message model as customer conversations.

> **Frontend (this repo) — done.** Implemented: `TeamChatApi` service, `TeamChatController`, `/team` routes, team chat Blade views (split-pane list + thread + modals), `initTeamChat()` JS (SPA thread loading, new chat, add members, SSE), sidebar nav link, EN+ID lang keys. `php -l` + Pint + `npm run build` pass.

---
## API contract — frontend handoff
Bearer JWT (company + user from token). Full bodies: `documentation/features/omnichannel-internal-team-chat.md` + Postman "Team Chat".
- `POST /api/v1/team/conversations` (direct: `{scope:"direct",user_id}`; group: `{scope:"group",title,member_user_ids[]}`) → 201 conversation
- `GET /team/conversations` → member-scoped list · `GET /team/conversations/{id}/messages` (403 non-member)
- `POST /team/conversations/{id}/messages` `{body}` → 201 internal message · `POST /team/conversations/{id}/members` `{member_user_ids[]}` → 204

## Acceptance criteria
- [x] A user can start a 1:1 chat with a teammate and create a named group channel within their company. _(`Team.CreateDirect`/`CreateGroup`; `TestTeamCreateDirectAddsBothMembers`.)_
- [x] Only conversation members can see and post in an internal conversation. _(`requireMember` via `IsMember` → 403; `TestTeamMembershipGatesPostAndThread`.)_
- [x] Internal messages are never delivered to any external channel. _(direction `internal`, appended via the model — the dispatcher is never invoked for team chat; asserted in the test.)_
- [x] Internal chat reuses the same conversation/message model as customer conversations. _(`kind=internal`,`channel=internal` on `conversation`/`participant`/`message` — no new tables.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass; `usecase/omnichannel` Team suite covers create direct/group, title-required, and membership gating (member vs outsider). Wired in `main.go` under `RequireJWT`. `make sqlc` regenerated (`ListInternalConversationsForUser`, `IsConversationMember`). `make lint` fails only on the pre-existing toolchain bug.
- (Frontend team-chat UI is Laravel — out of scope here.)
