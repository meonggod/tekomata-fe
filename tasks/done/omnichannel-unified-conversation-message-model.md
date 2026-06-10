---
name: '[STORY] Omnichannel: unified conversation & message model'
priority: 2
status: done
tags:
    - omnichannel
    - foundation
    - backend
---

A single tenant-scoped data model that can hold any conversation — a customer DM, a WhatsApp
group, an internal teammate chat, or a team channel — and any message inside it, normalized across
every channel. Everything else in the omnichannel epic builds on this one shape.

## Backend
**Goal:** define the channel-agnostic, company-scoped schema and domain types for conversations,
participants, contacts, and messages.

### `conversations` table + migration — _engineer_
- **Goal:** one row per conversation thread, regardless of channel or kind.
- **Background:** company-scoped (the company is the tenant). Carries `kind` (external/internal),
`scope` (direct/group), `channel` (whatsapp/instagram/email/web/internal), a channel-native thread
ref, title, `status` (open/pending/closed/snoozed), `assignee` (user, nullable), and AI state
(`ai_active` vs human-owned). Generalizes how `whatsapp-inbound` already ties a message to a tenant.

### `contacts` table — _engineer_
- **Goal:** an external person reachable on one or more channels.
- **Background:** company-scoped, distinct from internal `users`. Holds channel identities (WhatsApp
number, Instagram user id, email address, web visitor id) so the same human across channels can be
merged later.

### `participants` table — _engineer_
- **Goal:** who is in a conversation — polymorphic over a contact (external) or a user (internal).
- **Background:** references either a contact or a user, plus a per-conversation role. External groups
hold many contacts; internal groups many users. The AI is a system participant so its replies have
an author.

### `messages` table + migration — _engineer_
- **Goal:** one normalized message row for every inbound/outbound message on any channel.
- **Background:** belongs to a conversation, authored by a participant (contact / user / AI). Normalized
fields: direction (in/out/internal), body, attachments, channel-native message id, delivery status,
timestamps, and a reply/thread ref (IG comment threads, email replies). Channel-specific extras live
in a JSON column — never per-channel columns.

### Domain types + repository — _ai-agent_
- **Goal:** Go domain types and a thin repository so handlers stay slim and tenant-scoped.
- **Background:** every read/write filters by company id (multi-tenant). No global cache — load per
request, like `lookup-core`.

## Acceptance criteria
- [x] A conversation can be created as external-direct, external-group, internal-direct, or
internal-group, always scoped to a company. _(`conversation.kind`×`scope`; `Conversations.Open` validates the combo.)_
- [x] A message can be stored against any conversation with its author (a contact, a user, or the AI),
direction, body, and attachments. _(`message` + polymorphic `participant`; `Conversations.AppendMessage`.)_
- [x] A single contact can hold WhatsApp, Instagram, email, and web identities at once. _(`contact` row holds all four nullable identities, partial-unique each.)_
- [x] Channel-specific data is persisted without adding per-channel columns (JSON extras). _(`message.attachments` / `message.extras` jsonb only.)_
- [x] All conversation/message reads are filtered by company id; no cross-tenant leakage. _(every `Repository` method takes `companyID`; every query filters `company_id = $`.)_

### Verification (2026-06-10)
- `go build ./...`, `go vet ./...`, `go test ./...` — all pass (incl. `usecase/omnichannel` fake-repo
  suite covering enum validation, exactly-one-author, append defaults + last_message_at touch, contact resolve).
- `make sqlc` regenerated cleanly from migration `00011` + `db/queries/omnichannel.sql`.
- `make lint` fails **only** at a pre-existing toolchain bug (`golangci-lint@v1.61.0` cannot compile
  against Go 1.25 — same failure on a clean tree); not a code issue. Migration not applied to a live DB
  here (no Postgres container), but sqlc parsed the schema successfully.
- No HTTP endpoints / Postman this story (foundation only) — the inbox story adds the first.

---

## API contract — frontend handoff (build in parallel)

> **This is a foundation story — it introduces NO HTTP endpoints.** The conversation,
> message, contact and participant tables + the Go domain model are the deliverable;
> the inbox/channel stories expose them over HTTP. This section documents the **canonical
> JSON projection** of each entity so the FE can build its inbox views against a stable
> shape now, and so later stories (`unified-agent-inbox`, the channel stories) reuse it
> verbatim. There is therefore no Postman request for this story (the inbox story adds
> the first one).

### Entity: `Conversation`
```json
{
  "id": "8c1f...uuid",
  "kind": "external",
  "scope": "direct",
  "channel": "whatsapp",
  "channel_thread_ref": "6281234567890",
  "title": "",
  "status": "open",
  "assignee_user_id": null,
  "ai_active": true,
  "last_message_at": 1749513600000,
  "created_at": 1749513600000,
  "updated_at": 1749513600000
}
```
- `kind` ∈ `external | internal`
- `scope` ∈ `direct | group`
- `channel` ∈ `whatsapp | instagram | email | web | internal`
- `status` ∈ `open | pending | closed | snoozed`
- `ai_active` — `true` = the assistant owns the thread; `false` = a human has taken over.

### Entity: `Contact` (an external person; one row holds every channel identity)
```json
{
  "id": "1a2b...uuid",
  "display_name": "Budi",
  "whatsapp_number": "+6281234567890",
  "instagram_user_id": "17841400000000000",
  "email": "budi@example.com",
  "web_visitor_id": "vis_abc123",
  "created_at": 1749513600000,
  "updated_at": 1749513600000
}
```
Any identity field may be `null`; the same human merged across channels keeps one row.

### Entity: `Participant` (polymorphic author — exactly one of contact / user / AI)
```json
{
  "id": "3c4d...uuid",
  "conversation_id": "8c1f...uuid",
  "contact_id": "1a2b...uuid",
  "user_id": null,
  "is_ai": false,
  "role": "member",
  "created_at": 1749513600000
}
```

### Entity: `Message`
```json
{
  "id": "9e8f...uuid",
  "conversation_id": "8c1f...uuid",
  "participant_id": "3c4d...uuid",
  "direction": "in",
  "body": "Halo, stok masih ada?",
  "attachments": [
    { "url": "https://.../img.jpg", "type": "image", "name": "img.jpg", "size": 20481 }
  ],
  "channel_message_id": "wamid.HBg...",
  "delivery_status": "delivered",
  "reply_to_message_id": null,
  "extras": { "wa_context": "..." },
  "created_at": 1749513600000
}
```
- `direction` ∈ `in | out | internal`
- `delivery_status` ∈ `queued | sent | delivered | read | failed`
- `attachments` — JSON array (empty `[]` when none); `extras` — channel-specific JSON object (`{}` when none). **No per-channel columns ever.**

### Tenancy
Every row carries `company_id` (FK `company`). Every repository read/write is scoped by the
`company_id` from the request `Principal` — never from a request body. The `company_id` is not
part of the JSON projection (it is implied by the authenticated tenant).

See `documentation/features/omnichannel-conversation-model.md` for the full schema, Mermaid
flow, repository port, and the migration plan.
