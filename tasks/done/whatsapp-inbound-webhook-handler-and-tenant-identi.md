---
name: '[STORY] WhatsApp inbound: webhook handler and tenant identification by phone number'
priority: 1
status: done
tags:
    - core-loop
    - inbound
    - webhook
    - whatsapp
---

Every WhatsApp message sent to a tekomata-enabled company arrives as a webhook from the WhatsApp
Business API. The inbound handler receives it, identifies which company the message belongs to
(by the registered WhatsApp phone number), extracts the sender's message, and hands it off to the
assistant. This is the entry point of the core loop — without it, no questions ever reach the system.

## Backend
**Goal:** receive WhatsApp webhooks, identify the tenant by phone number, extract the message,
and route to the assistant.

### Company WhatsApp number schema + registration — _engineer_
- **Goal:** each company registers the WhatsApp number(s) it uses for the assistant.
- **Background:** add a `whatsapp_number` column to the `companies` table (or a separate
`company_whatsapp_numbers` for multiple numbers). The owner sets this via the panel or API. This
number is configured in the WhatsApp Business API to send webhooks to tekomata. Verified/active
only.

### `POST /webhooks/whatsapp` — webhook receiver — _engineer_
- **Goal:** accept incoming webhook POSTs from the WhatsApp Business API.
- **Background:** verifies the webhook signature (if WhatsApp signs payloads), extracts the
recipient phone number (the company's registered number) and the sender's message text/body.
Rejects non-message events (delivery receipts, etc.) — only inbound text messages are processed.
Responds quickly (200 OK) to avoid WhatsApp retries; actual processing happens asynchronously.

### Tenant resolution by phone number — _ai-agent_
- **Goal:** given a WhatsApp number the message was sent to, resolve the owning company.
- **Background:** queries the `companies` table by `whatsapp_number`; returns the company or a
clean "not registered" error. This is how the inbound handler knows whose catalog to query.
Pure read, no side effects.

### Message queue + handoff to assistant — _engineer_
- **Goal:** once the tenant is identified, queue the message for assistant processing.
- **Background:** the webhook handler writes to a work queue (or invokes the assistant directly
with a timeout) with: company id, sender phone number, message text, and WhatsApp message id
(for deduplication and reply correlation). The assistant (built in a separate story) consumes this
and produces the reply.

## Frontend
**Goal:** a control-panel screen where the owner registers their WhatsApp number.

### WhatsApp settings route + controller — _ai-agent_
- **Goal:** drive WhatsApp number registration from the panel.
- **Background:** posts to the Go endpoint for the active company and renders the result —
registered number, validation errors.

### WhatsApp settings view — _engineer_
- **Goal:** the UI to register and view the company's WhatsApp number.
- **Background:** shows the registered WhatsApp number (if any), an input to add/change it, and
instructions for configuring the WhatsApp Business API webhook URL. Warns that the number must
match what's configured in the WhatsApp Business API dashboard.

## Acceptance criteria
- [x] A company can register a WhatsApp phone number via the panel or API.
- [x] The webhook endpoint accepts POSTs from the WhatsApp Business API and responds quickly.
- [x] Given an inbound message, the handler resolves the owning company by the registered WhatsApp
number.
- [x] Messages from unregistered numbers are rejected with a clean error.
- [x] The handler extracts the sender's message text and queues it for assistant processing with
company id, sender, and WhatsApp message id.
- [x] Non-message events (delivery receipts, etc.) are acknowledged but ignored.
- [x] The panel shows the registered WhatsApp number and lets the owner change it. _(Frontend — Laravel repo)_

> **Frontend (this repo) — done.** Delivered by the **company settings** page: the "WhatsApp
> Numbers" card (`resources/views/settings/index.blade.php`, `#whatsapp` section) lists the
> company's registered numbers with the primary (`priority 0`) badge, and lets the owner add a
> number, promote one to primary, or remove one — backed by `CompanySettingsController`
> (`addWhatsapp` / `promoteWhatsapp` / `deleteWhatsapp`) over routes `settings.whatsapp.{add,promote,delete}`
> via `CompanySettingsApi`. Strings in `lang/{en,id}/messages.php` (`settings.whatsapp.*`).

---

## ✅ Delivered by the omnichannel epic (already shipped)

This story was completed as part of the **omnichannel epic** (see repo-root `CLAUDE.md`
→ "Omnichannel — WhatsApp channel" and "channel connector abstraction"). All `## Backend`
units map to shipped code:

| Story unit | Shipped implementation |
|---|---|
| Company WhatsApp number schema + registration | `company_whatsapp_number` table (migration `00010`); registered via `POST /api/v1/settings/whatsapp-numbers` (the priority-`0` number is the inbound tenant identifier) |
| `POST /webhooks/whatsapp` webhook receiver | `internal/resource/whatsapp/connector.go` (`ParseInbound`, `ValidateSignature`) + `handler.MetaWebhook` mounted at `router.go:347-348` (GET handshake + signed POST); answers 200 fast, non-message events → `ErrIgnoredEvent` |
| Tenant resolution by phone number | `internal/resource/postgres/tenant_resolver_repo.go` (`TenantResolverRepository`, WhatsApp via `company_whatsapp_number`); unresolved → `omnichannel.tenant_not_resolved` |
| Message queue + handoff to assistant | `usecase/omnichannel.Ingestor.RouteInbound` (dedupe on `channel_message_id`, resolve contact + conversation, append message) → async AI hook (`Ingestor.SetInboundHook`, run via `safego`) drafts/sends the reply when `ai.api_key` is configured |

No new backend code required — verified against the shipped pipeline and moved to `done/`.
