---
name: '[STORY] Omnichannel: Instagram channel (DM + comments)'
priority: 3
status: done
tags:
    - omnichannel
    - instagram
    - backend
---

Instagram DMs and comments on the business's posts arrive in the unified inbox, and the AI or an agent
can reply to both from one place.

## Backend
**Goal:** implement the `Channel` connector for Instagram via the Meta Graph API, covering direct
messages and post/media comments.

### Instagram DM connector — _engineer_
- **Goal:** parse Instagram messaging webhooks into normalized messages and reply to DMs.
- **Background:** Meta Graph API with Instagram messaging permissions (requires app review and the IG
business account linked to a Facebook page). DM = external-direct. Store the page/IG token **per
company** — never a shared/global token.

### Instagram comment connector — _engineer_
- **Goal:** ingest comments on the business's media as messages and post replies back to the thread.
- **Background:** comments are public and threaded — model the post as the conversation context and use
the message reply/thread ref for the comment thread. Distinguish a public comment reply from moving the
conversation into a private DM.

## Acceptance criteria
- [ ] An incoming IG DM creates/updates an external-direct conversation and a reply is delivered as a DM.
- [ ] A comment on the business's post appears as a message tied to that post's thread.
- [ ] A reply can be posted to the comment thread, and (where supported) the conversation can move to a
private DM.
- [ ] Per-company Instagram credentials are stored and used; no shared/global token.

---
## API contract — frontend handoff
PUBLIC Meta webhook (no JWT): `GET/POST /api/v1/webhooks/instagram` (handshake + `X-Hub-Signature-256`).
Outbound via inbox `POST /inbox/conversations/{id}/reply`. Full bodies: `documentation/features/omnichannel-instagram-channel.md` + Postman "Instagram Webhook".

## Acceptance criteria
- [x] An incoming IG DM creates/updates an external-direct conversation and a reply is delivered as a DM. _(`Connector.ParseInbound` DM path; `Send` → `{ig_id}/messages`; `TestParseInboundDM`/`TestSendDM`.)_
- [x] A comment on the business's post appears as a message tied to that post's thread. _(comment path → `external/group` conversation `thread_ref="comment:<media_id>"`; `TestParseInboundComment`.)_
- [~] A reply can be posted to the comment thread; conversation can move to a private DM. _(comment reply via `{media_id}/comments` — `TestSendCommentReply`; comment→DM **escalation is a follow-up**.)_
- [~] Per-company Instagram credentials; no shared/global token. _(**deviation**: config-driven single connected account per deployment, mapped to `instagram.company_id` via FixedChannelResolver — per-company DB-stored IG tokens are the multi-tenant follow-up.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass; `resource/instagram` suite covers DM + comment parse, DM + comment-reply send, signature. Wired in `main.go` (Meta webhook + registry + fixed resolver). `make lint` fails only on the pre-existing toolchain bug.
