---
name: '[STORY] Omnichannel: conversation thread — paginated history, jump-to-message & chat alignment'
priority: 3
status: done
tags:
    - omnichannel
    - inbox
    - pagination
    - backend
    - frontend
---

Opening a conversation in the agent inbox loads the most recent messages instantly, and scrolling up
pulls older history in pages of 50 — so a long-running thread stays fast to open but its full history
is always reachable by scrolling back. A conversation can also be opened focused on a specific message
(via a query param) — e.g. from a search hit or a reply reference — which loads that message centered
with 20 messages before and 20 after, and lets the agent keep scrolling in either direction from there.

## Backend
**Goal:** serve a conversation's messages newest-first in fixed pages of 50, with cursors so the
client can keep walking history in either direction — and also serve a window centered on a specific
referenced message.

### `GET /conversations/{id}/messages` — paginated fetch — _engineer_
- **Goal:** return a page of messages for a conversation with cursors for older and newer pages, and
support fetching the window around a referenced message.
- **Background:** reads the `messages` table from the shipped unified-conversation model, filtered by
the active company (tenant-scoped, no cross-tenant leakage). Three modes on one endpoint: default
returns the latest 50; a `before` cursor returns the 50 messages immediately older than it; an `after`
cursor returns the messages immediately newer (used when paging down from a centered window). Use
keyset/cursor pagination (on message id or created-at), not offset — new inbound messages must not
shift or duplicate rows across pages. Response carries whether more history exists in each direction.

### `around` message reference — centered window — _engineer_
- **Goal:** given a target message id, return that message with 20 before and 20 after (each side
capped to what exists).
- **Background:** an `around=<message_id>` query param resolves the target message in this conversation
and returns the symmetric window — fewer than 20 on a side if the target is near the start or end.
The target id is validated to belong to this conversation and company (404 otherwise — no
cross-tenant or cross-conversation peeking). The response marks the target so the client can focus
it, and returns both older (`before`) and newer (`after`) cursors so the agent can keep scrolling
out from the anchor in both directions.

### Cursor + page-size contract — _ai-agent_
- **Goal:** stable, opaque older/newer cursors and fixed page sizes the frontend can rely on.
- **Background:** cursors encode the boundary message so "fetch older"/"fetch newer" is deterministic
under concurrent inbound traffic. Page size is fixed server-side — 50 for history pages, 20-each-side
for the centered `around` window; the client never sets sizes. Pairs with the shipped
realtime-delivery push (new messages arrive live; this endpoint only serves history/windows).

## Frontend
**Goal:** the inbox thread view opens at the latest messages and lazy-loads older pages as the agent
scrolls up — or opens focused on a referenced message and lets the agent scroll out in both directions.

### Thread view infinite-scroll-up — _engineer_
- **Goal:** load the latest 50 on open, and fetch+prepend the next older 50 when the agent scrolls to
the top.
- **Background:** lives in the shipped agent-inbox thread view. On reaching the top, calls the endpoint
with the current `before` cursor, prepends the older page, and preserves scroll position so the view
doesn't jump. Stops when the server reports no more history. New live messages from realtime-delivery
still append at the bottom independently.

### Focus-on-referenced-message — _engineer_
- **Goal:** when the thread is opened with a message reference, jump straight to that message with its
surrounding context and highlight it.
- **Background:** the conversation can be opened with a message id in the URL/query (from a search
result or a reply link). The view calls the endpoint with `around=<message_id>`, renders the centered
window, scrolls the target into view, and briefly highlights it. From there, scrolling up pages older
(`before`) and scrolling down pages newer (`after`) until each end is reached — then the bottom
reconnects to live realtime updates as usual.

### Message alignment by direction — _engineer_
- **Goal:** render outbound (sent-by-us) messages right-aligned and inbound (from the contact)
left-aligned, so the thread reads like a normal chat.
- **Background:** uses the existing `direction` field on the message (in/out/internal) from the shipped
unified-conversation model — outbound (the company side: an agent reply or an AI reply, i.e. anything
this side sends) sits on the right; inbound from the external contact sits on the left. A message
newly sent from the composer is outbound, so it renders on the right immediately. Alignment is
consistent across history pages and the centered `around` window — it's a render rule on direction,
not on load order.

## Acceptance criteria
- [x] Opening a conversation shows the most recent 50 messages, newest at the bottom. _(latest mode → 50, ascending)_
- [x] Scrolling to the top loads the previous 50 older messages and prepends them without losing the
agent's scroll position. _(backend serves `?before=older_cursor`; prepend/scroll-preserve is FE)_
- [x] Paging continues 50 at a time until the start of the conversation, then stops cleanly (no more
history is indicated). _(verified: before → final 17-row page with `has_more_older=false`)_
- [x] No message is skipped or duplicated across pages, even while new messages arrive concurrently.
_(keyset on `(created_at, id)`; unit test walks all rows with no gap/dup)_
- [x] Opening a conversation with a message reference loads that message centered with up to 20 before
and 20 after, scrolls to it, and highlights it. _(backend: `?around=` window + `target_id`; scroll/highlight is FE)_
- [x] Near the start or end of a thread, the centered window returns fewer than 20 on the short side
without error. _(verified: 18 before + target + 20 after = 39)_
- [x] From a centered window the agent can page both older and newer until each end is reached.
_(both `older_cursor` + `newer_cursor` returned in every mode)_
- [x] A referenced message id that doesn't belong to the conversation (or company) is rejected — no
cross-tenant or cross-conversation access. _(404 `omnichannel.message_not_found`)_
- [x] All message reads are scoped to the active company — no cross-tenant access. _(conv ownership check + company-scoped queries)_
- [x] _(Frontend)_ Outbound (sent-by-us) messages render right-aligned; inbound left-aligned — pure render
rule on the `direction` field the API already returns; no backend work.
- [x] _(Frontend)_ A message just sent from the composer appears right-aligned immediately.

> **Frontend (this repo) — done.** All three `## Frontend` units implemented in the shipped
> agent-inbox thread view (`/app/inbox`), built against the documented cursor contract:
> - **Infinite-scroll-up history**: `InboxApi::messages()` now takes a cursor query
>   (`before`/`after`/`around`); `InboxController::threadJson` passes cursors through (and skips the
>   extra conversation fetch on plain scroll). JS `loadOlder()` fires near the top, **prepends** the
>   older page and **preserves scroll position**, and stops when `has_more_older` is false. Cursors
>   come from the server-embedded `page` JSON on direct load, or `renderThread(…, page)` on SPA open.
> - **Focus-on-referenced-message**: `/app/inbox/{id}?message=<id>` → controller fetches the `around`
>   window; JS scrolls the `target_id` into view and flashes it (`.msg-flash`), with `loadOlder`/
>   `loadNewer` paging out in both directions. `loadThread(id, {around})` supports the SPA path too.
> - **Alignment by `direction`**: `in`→left, `out`→right, `internal`→note (normalised in Blade + JS);
>   a composer reply is marked `__outbound` so it renders right immediately. Live SSE messages append
>   at the bottom but **no longer yank** an agent who's scrolled up reading history (near-bottom check).
>
> Degrades gracefully where the local Go API doesn't yet return `page` (null → no paging, still
> renders + aligns). Verified: Pint clean, `php artisan test` 13/13 green, assets rebuilt. Backend-only
> criteria left as the backend ticked them.
- [x] Live incoming messages still appear at the bottom in real time, independent of history paging.
_(unchanged SSE path; this endpoint only serves history/windows)_
- [x] Tests pass: `go vet ./...` + `go test ./...` green (lint toolchain is broken repo-wide; `-race` needs cgo).

---

## API contract — frontend handoff (build in parallel)

One endpoint serves all three modes. **Postman** is the executable source of truth
(`postman/tekomata.postman_collection.json` → _Omnichannel / Inbox_). Mirror +
diagrams in `documentation/features/omnichannel-conversation-thread.md`.

### `GET /api/v1/inbox/conversations/{id}/messages`
- **Auth:** JWT (`Authorization: Bearer <access_token>`). Tenant-scoped — the company
  is taken from the token's `Principal`, never the request. No cross-tenant access.
- **Path:** `id` — conversation UUID.
- **Query params** (all optional; precedence: `around` > `before` > `after` > none):
  | param | meaning |
  |-------|---------|
  | _(none)_ | **latest page** — the most recent 50 messages |
  | `before=<cursor>` | the 50 messages immediately **older** than the cursor (scroll up) |
  | `after=<cursor>`  | the 50 messages immediately **newer** than the cursor (scroll down) |
  | `around=<message_id>` | **centered window** — the target message with up to 20 older + 20 newer |
- **Page size is fixed server-side** (50 history / 20-each-side around). The client
  never sets a size; `limit`/`offset` are ignored on this endpoint.
- **Cursors are opaque** (base64url) — the client only ever echoes back the
  `older_cursor` / `newer_cursor` values it received. Never construct them.
- **Ordering:** `messages` is always **ascending (oldest → newest)** regardless of mode,
  so the FE renders top→bottom and the newest sits at the bottom.
- **Keyset/cursor pagination** on `(created_at, id)` — new inbound messages never shift
  or duplicate rows across pages.

**Success — `200 OK`** (default / latest page):
```json
{
  "data": {
    "messages": [
      {
        "id": "0b6b...e1",
        "conversation_id": "9c2f...a4",
        "participant_id": "3d5e...77",
        "direction": "in",
        "body": "Halo, apakah produk Signature masih tersedia?",
        "attachments": [],
        "channel_message_id": "wamid.SEED1.1",
        "delivery_status": "delivered",
        "reply_to_message_id": null,
        "extras": {},
        "created_at": 1781265733044
      },
      {
        "id": "1a7c...92",
        "conversation_id": "9c2f...a4",
        "participant_id": "8f1a...0b",
        "direction": "out",
        "body": "Halo Budi! Produk Signature masih tersedia.",
        "attachments": [],
        "channel_message_id": "wamid.SEED1.2",
        "delivery_status": "sent",
        "reply_to_message_id": null,
        "extras": {},
        "created_at": 1781265734044
      }
    ],
    "page": {
      "older_cursor": "MTc4MTI2NTczMzA0NDowYjZiLi4uZTE",
      "newer_cursor": "MTc4MTI2NTczNDA0NDoxYTdjLi4uOTI",
      "has_more_older": true,
      "has_more_newer": false,
      "target_id": null
    }
  }
}
```

**`page` object:**
| field | type | meaning |
|-------|------|---------|
| `older_cursor` | string \| null | echo as `?before=` to fetch the next older page (null when the page is empty) |
| `newer_cursor` | string \| null | echo as `?after=` to fetch the next newer page |
| `has_more_older` | bool | older history exists beyond this page (keep scrolling up) |
| `has_more_newer` | bool | newer history exists beyond this page (keep scrolling down). On the **latest** page this is `false` — the bottom reconnects to realtime |
| `target_id` | string \| null | set **only** in `around` mode — the referenced message to focus/highlight |

**`around` mode** (`?around=<message_id>`) returns the same envelope with the target
plus up to 20 messages on each side (fewer near the start/end of the thread), `target_id`
set, and both cursors populated so the agent can scroll out in either direction.

**Errors:**
- **`404` conversation not found / not in this company:**
```json
{ "error": { "code": "omnichannel.conversation_not_found", "message": "conversation not found", "request_id": "req_abc123" } }
```
- **`404` `around` message id absent / not in this conversation (no cross-tenant or cross-conversation peeking):**
```json
{ "error": { "code": "omnichannel.message_not_found", "message": "message not found", "request_id": "req_abc123" } }
```
- **`422` malformed cursor or `around` id:**
```json
{ "error": { "code": "validation_failed", "message": "one or more fields are invalid", "request_id": "req_abc123",
  "fields": [ { "field": "before", "code": "validation.invalid_value" } ] } }
```
- **`401` missing/invalid token:** `{ "error": { "code": "unauthorized", "message": "authentication required", "request_id": "…" } }`

> **Message alignment (FE render rule, no API change):** the existing `direction` field
> drives it — `out` (agent/AI reply, this side) right-aligned; `in` (external contact)
> left-aligned; `internal` notes as the FE prefers. A message just sent from the composer
> is `out`, so it renders right immediately.
