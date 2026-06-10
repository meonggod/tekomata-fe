---
name: '[STORY] Omnichannel: channel connector abstraction + inbound/outbound dispatch'
priority: 2
status: done
tags:
    - omnichannel
    - foundation
    - backend
---

Every channel plugs into one interface: inbound events become normalized messages on a conversation,
and outbound replies are dispatched to the right channel. Adding a channel — or sending on one — never
touches the inbox or the AI code.

## Backend
**Goal:** a `Channel` connector interface, an inbound ingestion pipeline, and an outbound dispatcher,
all channel-agnostic and testable without the network.

### `Channel` connector interface — _engineer_
- **Goal:** a narrow interface every channel implements — parse an inbound payload into a normalized
message, send a normalized message to the channel's API, and resolve tenant + contact from an event.
- **Background:** mirrors the repo's interface-first style (`SprintLister`, `Messenger`) so each channel
is unit-testable with a fake and never leaks `net/http` upward. WhatsApp / Instagram / email / web each
become an implementation.

### Inbound ingestion pipeline — _engineer_
- **Goal:** turn a raw inbound event into (tenant, contact, conversation, message) and persist it,
creating the contact/conversation when new.
- **Background:** generalizes the tenant identification `whatsapp-inbound` does by WhatsApp number to a
per-channel identity. Idempotent on the channel-native message id so webhook retries don't duplicate.

### Outbound dispatcher — _engineer_
- **Goal:** take a normalized outbound message and deliver it through the conversation's channel
connector, recording delivery status.
- **Background:** a single send path (like the clickup client's `do()`), with retry/backoff. Channel
send-window rules (e.g. WhatsApp's 24-hour session window) surface as typed errors, not silent drops.

### `RouteInbound` / `Dispatch` entry points — _ai-agent_
- **Goal:** the two thin functions the channel webhooks and the AI/agent reply paths both call.
- **Background:** keep handlers thin; centralize routing and dispatch so they stay testable with fakes.

## API contract — frontend handoff (build in parallel)

> **No HTTP endpoints in this story** — it is the connector seam (interface + inbound pipeline +
> outbound dispatcher + tenant resolver). Per-channel **webhook** routes (which call `RouteInbound`)
> ship with each channel story; the **agent-reply** endpoint (which calls `Dispatch`) ships with the
> unified-agent-inbox story. No Postman request here. The implementer-facing contract (the `Connector`
> / `TenantResolver` / `ConnectorRegistry` interfaces, `RawInbound`/`InboundMessage`/`OutboundMessage`,
> and the new error codes) is documented in `documentation/features/omnichannel-channel-connector.md`.

## Acceptance criteria
- [x] A new channel is added by implementing the connector interface only — no changes to inbox, AI,
or model code. _(`domain.Connector`; `Registry` wires it; pipeline/dispatcher depend only on the port.)_
- [x] An inbound event for a known contact appends to the existing conversation; for a new contact it
creates the contact + conversation. _(`Ingestor.ingestOne`: FindConversationByThread else Open; UpsertContactByIdentity.)_
- [x] Duplicate webhook deliveries (same channel-native id) are ignored. _(dedup via `FindMessageByChannelID` + `uq_message_channel_id`; `TestRouteInboundCreatesThenDedups`.)_
- [x] An outbound message is delivered through the correct channel and its delivery status is recorded.
_(`Dispatcher.Dispatch` → connector.Send → AppendMessage with status; `TestDispatchSendSuccess`.)_
- [x] Channel send-window violations surface as typed errors, not silent failures. _(`apperr.SendWindowClosed`, terminal — recorded as a `failed` message + returned; `TestDispatchSendWindowClosedRecordsFailure`.)_

### Verification (2026-06-10)
- `go build ./...`, `go vet ./...`, `go test ./...` — all pass. New `usecase/omnichannel` tests cover the
  pipeline (create-then-dedup, ignored event, unknown channel) and the dispatcher (send success, terminal
  send-window failure recorded, transient-retry-then-success) against a stateful in-memory repo + fake connector.
- `make sqlc` regenerated cleanly (added `FindCompanyByWhatsAppNumber` for tenant resolution).
- `make lint` fails only at the pre-existing `golangci-lint`↔Go 1.25 toolchain bug (same on a clean tree).
- Not wired in `cmd/api/main.go` yet (no channel webhooks / inbox endpoint to mount); wiring lands with
  the first channel + the inbox story.
