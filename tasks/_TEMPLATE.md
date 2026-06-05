# [STORY] <title>

<one-paragraph goal + background: what the user gets and why>

**ClickUp:** <card-id> · **Priority:** <1–4>

---

## Backend
> _Context only — built in the Go API, NOT in this repo. Listed so the frontend
> knows the contract it consumes._

### <endpoint / capability>
<what the API does>

---

## Frontend
> _This repo's work. Implement only these units._

### <unit> _engineer_ | _ai-agent_
<goal + background; the "how" is decided in the repo>

---

## API contract
> _The exact request/response the frontend codes against. Mirror real payloads._

### `<METHOD> /api/<path>`

**Request**
```http
<METHOD> /api/<path> HTTP/1.1
Authorization: Bearer <jwt>
Content-Type: application/json
```
```json
{
  "field": "value"
}
```

**Response — 200 OK**
```json
{
  "data": {
    "id": "abc123",
    "field": "value"
  }
}
```

**Response — 4xx/5xx (error shape)**
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "human-readable reason"
  }
}
```

---

## Acceptance criteria
- [ ] observable condition that means done
- [ ] error/empty/loading states handled
- [ ] strings via `__('messages.<key>')` (ID + EN)
