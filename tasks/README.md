# tasks/

`[STORY]` taskdocs pulled from **ClickUp** (the source of truth for *what* to build). Each file
is one story to implement in this repo.

- ClickUp: workspace `9018840713` · Kanban board · list `901818578961`.
- Naming: `STORY-<clickup-id>-<slug>.md` (keep the id so it maps back to the card).

## Kanban folders
The story file lives in the folder that matches its state — move the file as work progresses:

- `todo/` — refined and ready; **new stories land here**.
- `doing/` — currently being implemented (keep this small — ideally one at a time).
- `done/` — shipped + acceptance criteria ticked. Kept for traceability.

Start a story by moving its file `todo/ → doing/`; finish by moving `doing/ → done/`.

## Story shape
Copy `_TEMPLATE.md`. A `[STORY]` has both a `## Backend` and a `## Frontend` section, but
**this repo implements only the `## Frontend` (Laravel) units** — the `## Backend` (Go) section
is context for the contract we consume, built elsewhere. Every story includes an
`## API contract` block with example **request and response** payloads (incl. the error shape)
so the frontend codes against a concrete contract.

```markdown
# [STORY] <title>
<one-paragraph goal + background>

## Backend            # context only — NOT built here
### <endpoint>
<what the API does>

## Frontend           # this repo's work
### <unit> _engineer_ | _ai-agent_
<goal + background; the "how" is the repo's job>

## API contract       # example request + response (+ error shape)
### `<METHOD> /api/<path>`
**Request** … **Response — 200 OK** … **Response — 4xx/5xx**

## Acceptance criteria
- [ ] observable condition that means done
```

## Flow
1. Export the refined story from ClickUp into a `STORY-*.md` in `todo/`.
2. Move it to `doing/`; implement the `## Frontend` units against the `## API contract`.
3. Tick the acceptance criteria, move the file to `done/`, advance the ClickUp card.

**Priority** (from `company.md`): 1 = core loop · 2 = foundational (auth, catalog import) ·
3 = metering/tiers/panel · 4 = nice-to-haves.
