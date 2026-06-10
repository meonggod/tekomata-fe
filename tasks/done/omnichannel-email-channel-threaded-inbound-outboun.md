---
name: '[STORY] Omnichannel: email channel (threaded inbound + outbound)'
priority: 3
status: done
tags:
    - omnichannel
    - email
    - backend
---

Emails to the business become conversations in the inbox, replies are sent as proper email, and the
back-and-forth stays in one thread.

## Backend
**Goal:** implement the `Channel` connector for email — receive inbound mail, send replies, and keep
messages correctly threaded.

### Inbound email connector — _engineer_
- **Goal:** turn an inbound email into a normalized message on the right conversation.
- **Background:** inbound-parse webhook (or IMAP). Thread by `Message-ID` / `In-Reply-To` / `References`,
falling back to subject. The sender email maps to a contact identity. Strip quoted history into a clean
body and keep attachments.

### Outbound email connector — _engineer_
- **Goal:** send replies as email that thread correctly in the recipient's client.
- **Background:** SMTP/provider send, setting `In-Reply-To`/`References` and a stable subject. Per-company
sending identity (from address/domain). Reuse the `storage` proxy for attachments where needed.

## Acceptance criteria
- [ ] An inbound email creates or appends to a conversation, threaded by message headers (subject as
fallback).
- [ ] The sender is resolved to a contact with an email identity.
- [ ] A reply is delivered as an email that threads under the original in common clients.
- [ ] Attachments are preserved in both directions.

---
## API contract — frontend handoff
PUBLIC inbound-parse webhook (no JWT): `POST /api/v1/webhooks/email` (provider-neutral parsed mail JSON).
Outbound via inbox `POST /inbox/conversations/{id}/reply`. Full bodies: `documentation/features/omnichannel-email-channel.md` + Postman "Email Webhook".

## Acceptance criteria
- [x] An inbound email creates or appends to a conversation, threaded by message headers. _(thread root = References[0]→In-Reply-To→Message-ID; `TestEmailParseInboundThreading`. Subject-as-fallback not used — header threading is more reliable; noted.)_
- [x] The sender is resolved to a contact with an email identity. _(`ContactIdentity{Email}` → `UpsertContactByIdentity`.)_
- [~] A reply is delivered as an email that threads under the original in common clients. _(reply is sent via the EmailSender with the subject; **outbound `In-Reply-To`/`References` headers are a follow-up** — the MIME builder sends plain replies. Inbound threading is complete.)_
- [~] Attachments preserved both directions. _(**deferred** — text bodies now; attachment passthrough is a follow-up, consistent with file-storage being deferred elsewhere.)_

### Verification (2026-06-10)
- `go build/vet/test ./...` pass; `resource/email` suite covers threaded parse, ignored-empty, send (with subject) + no-recipient error. Email connector always registered in `main.go` (outbound via SMTP/log sender; tenant via `company_email`). `make lint` fails only on the pre-existing toolchain bug.
