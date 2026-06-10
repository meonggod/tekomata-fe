---
name: '[STORY] Omnichannel: website chat (embeddable widget + channel)'
priority: 3
status: done
tags:
    - omnichannel
    - web-chat
    - backend
    - frontend
---

A business drops a chat widget on its website; visitor messages arrive in the unified inbox in real
time, and replies (AI or human) appear in the widget without a reload.

## Backend
**Goal:** a web-chat connector, a visitor session model, and the widget-facing endpoints.

### Web chat connector + visitor session — _engineer_
- **Goal:** accept visitor messages, map a visitor session to a contact + conversation, and deliver
outbound replies to the open widget.
- **Background:** an anonymous visitor id (cookie) becomes a web contact identity, upgradeable when the
visitor gives a name/email. External-direct conversation. Live push relies on the realtime-delivery
story.

### Widget-facing API (per-company embed key) — _engineer_
- **Goal:** the endpoints the widget calls to start a session, send, and receive.
- **Background:** scoped by a per-company **public** embed key (like the open-API keys, but public and
origin-restricted). Rate-limited and metered like other inbound.

## Frontend
**Goal:** the embeddable widget itself (standalone JS, not the Laravel panel UI).

### Embeddable chat widget — _engineer_
- **Goal:** a small JS snippet a business pastes in that renders the chat bubble + thread and talks to
the widget API.
- **Background:** loads via one script tag keyed by the company embed key; mobile-friendly. Independent
of the panel's Blade views.

## Acceptance criteria
- [ ] Adding one script tag with a company embed key renders a working chat widget on any site.
- [ ] A visitor message creates/updates an external-direct conversation and shows in the inbox in real
time.
- [ ] An AI or human reply appears in the widget without a page reload.
- [ ] An anonymous visitor can be upgraded to a named contact when they provide details.
- [ ] The embed key is origin-restricted and rate-limited.

---
## API contract — frontend handoff
PUBLIC widget endpoint (no JWT): `POST /api/v1/webhooks/web-chat` `{site_key(=company id),visitor_id,name?,body,client_message_id?}`.
Outbound via inbox `POST /inbox/conversations/{id}/reply`. Full bodies: `documentation/features/omnichannel-web-chat-channel.md` + Postman "Web Chat Widget".

## Acceptance criteria
- [x] Adding one script tag with a company embed key renders a working widget. _(**frontend/Laravel** — widget JS at `public/js/widget.js`; embed snippet shown in Settings → Company → Web Chat Widget section.)_
- [x] A visitor message creates/updates an external-direct conversation and shows in the inbox. _(`Connector.ParseInbound` → pipeline; appears via the inbox list/thread endpoints; `TestWebParseInbound`. "in real time" = the realtime story #10.)_
- [~] An AI or human reply appears in the widget without a page reload. _(backend records the outbound message; **live push is the realtime story #10** — until then the widget polls the thread.)_
- [~] Anonymous visitor upgraded to a named contact. _(name captured on inbound into the contact; **identity-merge/upgrade flow is a follow-up**.)_
- [~] Embed key origin-restricted and rate-limited. _(global per-IP rate limit applies; origins via `cors.allowed_origins`; **per-key origin restriction is a follow-up** — the site_key only identifies the tenant, it is not a secret.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass; `resource/webchat` suite covers enable gating, parse, ignored-empty, no-op send. Wired in `main.go` when `web_chat.enabled`; tenant resolver maps site_key→company (existence-checked). `make lint` fails only on the pre-existing toolchain bug.

> **Frontend (this repo) — done.** Implemented the embeddable chat widget (`public/js/widget.js`) and the settings-page embed-code section. Widget reads `data-site-key`, generates/persists `visitor_id` in localStorage, POSTs to `/api/v1/webhooks/web-chat`, polls for replies, and stores conversation locally. Settings → Company tab shows the pre-filled `<script>` snippet with a copy button. Lang keys added in EN + ID. `php -l` + Pint + `npm run build` pass.
