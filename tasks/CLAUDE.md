# tasks/ — working rules (read every time you touch a story)

> Folder meaning + story shape live in `README.md`. These are the **always-do** rules for
> moving stories through the board. Apply them automatically — don't wait to be asked.

## Move the story file as state changes
- **Start →** move the file `todo/ → doing/` the moment you begin implementing it.
- **Finish →** when the story's `## Frontend` units are implemented and its frontend
  `## Acceptance criteria` are satisfied, tick those boxes and move the file `doing/ → done/`.
  (Backend-only criteria stay as the backend left them; we only own the `## Frontend` units.)
- Keep `doing/` to **one story at a time** where possible.

### Do it automatically — don't ask first
- If the frontend work is finished, **move the file** — never pause to ask "should I move it to
  done?" or "is it finished?". Moving is the default conclusion of finishing.
- **Tick only the frontend-owned** acceptance boxes. Leave backend-only criteria (credential
  checks, token minting, DB writes, etc.) unticked — this repo can't prove them. A short
  `> **Frontend (this repo) — done.** …` note listing the implemented units is welcome.

## Then pick up the next story
- When a story is finished and moved to `done/`, immediately move on to the **next story in
  `todo/`**: move it `todo/ → doing/` and implement its `## Frontend` units. Repeat.
- If `todo/` is empty, stop and report that the board is clear.

## Reminder
- Implement **only** the `## Frontend` (Laravel) units; the `## Backend` (Go) section is context
  for the contract we consume. Build against the `## API contract` block.
- Per repo convention, **UI stories don't get unit/feature tests** — verify by rebuilding assets
  (`npm run build`/`dev`) + a manual check, and run Pint.
