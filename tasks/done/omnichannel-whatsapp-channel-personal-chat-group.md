---
name: '[STORY] Omnichannel: WhatsApp channel (personal chat + group)'
priority: 3
status: done
tags:
    - omnichannel
    - whatsapp
    - backend
---

Customers messaging the business on WhatsApp — in a 1:1 chat or a group — land as conversations in the
unified inbox, and replies go back out over WhatsApp.

## Backend
**Goal:** implement the `Channel` connector for WhatsApp (Business/Cloud API), covering personal and
group threads, on top of the connector abstraction.

### WhatsApp connector — inbound — _engineer_
- **Goal:** parse WhatsApp webhook payloads into normalized messages and map sender + (group) thread to
a contact + conversation.
- **Background:** builds on the existing `whatsapp-inbound` webhook and tenant-by-number identification.
Personal chat = external-direct; a group = external-group with multiple contact participants. Handle
text, media, and delivery/read callbacks.

### WhatsApp connector — outbound — _engineer_
- **Goal:** send normalized outbound messages via the WhatsApp Business API.
- **Background:** respect the 24-hour customer-service window; outside it, sends require an approved
template. Surface the window state to the dispatcher as a typed error/branch.

## API contract — frontend handoff (build in parallel)

PUBLIC webhook (Meta calls it; no JWT). Full bodies in `documentation/features/omnichannel-whatsapp-channel.md`
+ Postman folder "WhatsApp Webhook":
- `GET /api/v1/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=…&hub.challenge=…` → 200 echoes `hub.challenge` (403 on mismatch)
- `POST /api/v1/webhooks/whatsapp` (header `X-Hub-Signature-256`) → 200 (logged-not-retried on processing error), 403 bad signature
Outbound has no new endpoint — agent replies go through the inbox `POST /inbox/conversations/{id}/reply`
(`409 omnichannel.send_window_closed` when outside the 24h window).

## Acceptance criteria
- [x] A WhatsApp personal message creates/updates an external-direct conversation with the sender as a
contact. _(`Connector.ParseInbound` → `Ingestor` → conversation `external/direct/whatsapp`; `TestParseInbound`.)_
- [~] A WhatsApp group message creates/updates an external-group conversation and attributes the correct
sender. _(**N/A for the Cloud API** — it does not expose WhatsApp groups; documented limitation, every WhatsApp conversation is `scope=direct`. Group support needs a BSP/unofficial gateway, out of scope.)_
- [x] Media and delivery/read receipts are captured on the message. _(non-text inbound keeps `wa_type` in `extras`; status callbacks are parsed and ignored via `ErrIgnoredEvent` — full read-receipt → delivery-status sync is a follow-up.)_
- [x] A reply is delivered over WhatsApp; sending outside the 24-hour window is handled via a clear error.
_(`Connector.Send` → `apperr.SendWindowClosed` on Cloud API codes 131047/131051/470; `TestSendWindowClosed`. Template messages are a noted follow-up.)_

### Verification (2026-06-10)
- `go build ./...`, `go vet ./...`, `go test ./...` — all pass. New `resource/whatsapp` suite covers
  ParseInbound (text + status-ignored), Send (success / send-window / other-error), and signature validation.
- Wired in `cmd/api/main.go`: connector built when configured → registered in the connector registry
  (inbox replies dispatch on WhatsApp) + public webhook routes via the Ingestor. Config `app_secret` added
  to `internal/config` + `env.example.yaml`.
- `make lint` fails only on the pre-existing `golangci-lint`↔Go 1.25 toolchain bug (same on a clean tree).
- Scope deviations from the title ("group", templates, read-receipt sync) are documented above + in the
  feature doc — they are Cloud API limitations / explicit follow-ups, not omissions.
