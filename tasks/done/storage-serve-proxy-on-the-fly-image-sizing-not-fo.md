---
name: '[STORY] Storage serve proxy: on-the-fly image sizing + not-found placeholder'
priority: 3
status: done
tags:
    - storage
    - images
    - gcs
    - infrastructure
---

The original uploaded image is always kept pristine in storage, but when a caller fetches it they can
ask the storage proxy for a sized variant — e.g. `1000` means "fit inside a 1000×1000 box, aspect
ratio preserved": a portrait comes back at height 1000, a landscape at width 1000, a square at
1000×1000. The storage service produces and caches these derivatives on demand; consuming stories
(product images, KYC/KYB, etc.) just request the size they need instead of downloading the full
original. The serve proxy also gains a **default file-not-found placeholder** — a broken-image for
image requests, a not-found placeholder for video — returned whenever the URL is wrong or the
file/size can't be resolved, so a bad link degrades into an obvious broken-image rather than a bare
error. **Backend only — it extends the existing storage serve proxy and has no UI of its own.**

## Backend
**Goal:** extend the storage serve proxy so an image object can be requested at a bounded size,
resizing on the fly from the pristine original, fit-within-box with aspect ratio preserved, never
upscaling and never cropping; cache each derivative so repeat fetches are cheap.

### Sized serve on `GET /files/{id}` — _engineer_
- **Goal:** accept a size request on the existing serve proxy and return the image scaled to fit the
requested bounding box.
- **Background:** extends the shipped `private-file-storage` serve proxy (`GET /files/{id}` behind
`[storage.tekomata.com/](http://storage.tekomata.com/)<opaque-id>`). A size parameter (e.g. `?size=1000` or `1000x1000`) bounds the
**longest edge**: the image is scaled to fit inside that box with aspect ratio preserved — portrait
keeps its taller side at the bound, landscape its wider side — so it is never distorted or cropped,
and never enlarged past the original's pixels. The requested size must be one of the **configured
available sizes** (see the next unit) — an unsupported size returns the default broken-image
placeholder. Only image content-types are transformable; a size request on a non-image (or no size
requested) serves the original bytes unchanged. The same visibility/tenant-authorization and
not-found rules from the parent story still apply.

### Configurable allowed sizes — _ai-agent_
- **Goal:** restrict resizing to a **configured** set of available sizes so the endpoint can't be
abused to generate unbounded distinct derivatives.
- **Background:** the available bounding sizes are **driven by config** (e.g. a small catalogue like
100, 300, 1000), so the set can change without a redeploy — consistent with tekomata's DB-backed
config pattern. A sized request whose size is **not in the config** does **not** error out or fall
back to a nearest size; it is treated as unresolvable and answered with the proxy's default
placeholder (see the next unit). Keeping the size set bounded also caps cache cardinality and CPU
and blocks a resize-flood DoS.

### Default file-not-found placeholder on the serve proxy — _ai-agent_
- **Goal:** make the storage proxy degrade gracefully with a visible placeholder whenever a requested
file can't be served, instead of a bare error — the proxy's **default** for any unresolvable
request.
- **Background:** this is the storage proxy's default response for any request it can't resolve — a
**wrong/malformed URL**, an **unknown opaque id**, a **missing/deleted object**, an unsupported
file format, or (for images) an unsupported configured size. An **image** request returns the
default **broken-image** placeholder; a **video** request returns a video-appropriate "not found"
placeholder (or the same broken-image default) — a generic asset that visibly reads as *file not
found* to the end user. **It is returned as a `200` response with an image content-type and the
placeholder bytes** — not a `404` — because a `404` makes the browser render its own broken icon
instead of our placeholder; so to actually show our asset in an `<img>`, the proxy must serve real
image bytes with a success status. It carries **`Cache-Control: no-store`** so it is **never cached
as if it were the real object** (a later successful fetch of the real id isn't poisoned). The
placeholder is **identical whether the object is genuinely missing or the caller simply isn't
authorized**, so it still preserves the parent story's **no-existence-leak / IDOR** guarantee. This
applies to **image/video** requests; a request for a non-media file type keeps the parent story's
plain uniform not-found (`404`).

### Derivative cache with a refreshable TTL — _engineer_
- **Goal:** generate each (object, size) variant once, track it with a **lightweight cache record
that holds its TTL**, and **refresh that TTL every time the variant is (re)generated** — without
ever creating a second `files`/original record or issuing a new public id.
- **Background:** the pristine original keeps its single `files` row and stable opaque id — that
never changes and is the DB's source of truth. Each **derivative** is tracked by a separate
**lightweight cache record** keyed by (original opaque id + requested size), holding its GCS object
path and an `expires_at` (the TTL). Derivatives live under a **distinct, recognizable path/prefix
(or object label)** so the storage layer can **tell a derivative apart from an original upload** —
this is what lets TTL/cleanup target derivatives only, never an original. On a sized request the
backend looks up this record: on a hit (not expired, object present) it serves straight from GCS; on
a miss or after expiry it **regenerates from the pristine original** under the same (id + size) key,
writes a fresh GCS object, and **updates the cache record's TTL (`expires_at`)**. So a
frequently-accessed variant keeps getting its expiry pushed forward (sliding TTL) and stays warm,
while an untouched one lapses and is swept (GCS lifecycle + the record cleared) so derivatives don't
pile up in GCS. **Crucially, regeneration reuses the same original id and only writes a new
derivative GCS object + refreshes that derivative's TTL — the original's `files` row is never
touched and no new public id is issued**, so the DB stays consistent and the consumer-facing URL
(opaque id + size) is stable across every regeneration. Deleting the original (parent story's
`DELETE /files/{id}`) clears its derivatives and their cache records. For now this path is exercised
specifically by the product media gallery's on-the-fly resizing.

## Acceptance criteria
- [x] The uploaded original is stored pristine and is never modified by a sizing request.
- [x] A size request returns the image scaled to fit within an N×N box with aspect ratio preserved:
portrait → height N, landscape → width N, square → N×N; never distorted, never cropped.
- [x] An image is never upscaled beyond its original dimensions.
- [x] The available sizes are driven by config (changeable without a redeploy); only configured
sizes are honored, never an arbitrary dimension.
- [x] The serve proxy's **default** response for any unresolvable **image/video** request —
wrong/malformed URL, unknown id, missing/deleted object, unsupported file format, or (image)
unsupported size — is a visible placeholder: a broken-image for image requests, a not-found
placeholder for video. (A non-media file type keeps the plain `404` uniform not-found.)
- [x] The placeholder is returned as a **`200`** response carrying the image bytes with
**`Cache-Control: no-store`** — so the browser actually renders our placeholder in an `<img>`
and it is **not cached as the real object** (no poisoning of a later valid fetch of that id).
- [x] The placeholder is the **same** whether the object is genuinely missing or the caller is
unauthorized (no existence leak preserved).
- [x] A non-image object, or a request with no size, is served as the unchanged original bytes.
- [x] Each (object, size) derivative is generated once and cached; repeat fetches reuse it.
- [x] A derivative never gets its own `files`/original record or a new public id — it reuses the
original's opaque id + size; a **lightweight cache record** tracks each derivative's GCS path
and its TTL (`expires_at`).
- [x] The storage layer can distinguish a derivative object from an original upload (e.g. by
path/prefix or object label), so TTL/cleanup applies to derivatives only and never to originals.
- [x] Each derivative carries a TTL; an untouched derivative lapses and is swept (GCS lifecycle +
cache record cleared) so derivatives don't accumulate in GCS.
- [x] Regenerating a derivative reuses the **same original id** (opaque id + size), rebuilds from the
pristine original, writes a fresh GCS object, and **refreshes the cache record's TTL**
(`expires_at` pushed forward) — while the original's `files` row is unchanged and no new id is
issued, so the DB stays consistent and the consumer-facing URL is stable.
- [x] Sized serving honors the same visibility / tenant-authorization and uniform not-found rules as
the existing serve proxy.
- [x] Deleting an object also removes its cached derivatives.

> **Frontend (this repo) — nothing to build.** This story is explicitly *"Backend only — it extends
> the existing storage serve proxy and has no UI of its own."* It has no `## Frontend` section. The
> on-the-fly `?size=` contract it ships is *consumed* by the product media gallery
> (`products.media.*`), which appends `?size=` to the storage-proxy `url` when rendering
> thumbnails/previews. All acceptance criteria are backend-owned and were satisfied by the backend.

---

## API contract — frontend handoff (build in parallel)

This story **extends the existing serve proxy** (`private-file-storage`). No new route —
`GET /files/{id}` gains an optional `?size=` query param. Upload/delete are unchanged.
Postman: see `Files / Serve (sized)` in `postman/tekomata.postman_collection.json`.
Full design + Mermaid: `documentation/features/on-the-fly-image-sizing.md`.

### `GET /files/{id}?size={N}` — sized serve (public, optional JWT)

Serves an image scaled to **fit inside an N×N box**, aspect ratio preserved, never
upscaled, never cropped. `?size=1000` or `?size=1000x1000` both bound the **longest edge**
to 1000. `N` must be one of the **configured** sizes (`storage.image_sizes`, default
`100, 300, 1000`). Same visibility/tenant-auth rules as the parent story.

| Request | Result |
|---|---|
| no `size` (or `size` malformed) | the pristine original bytes, unchanged (`200`/`206`) |
| `size=N`, N configured, image, authorized | the sized variant — `200`, `Content-Type: image/jpeg` (or `image/png` for PNG sources), `Cache-Control` per visibility, `ETag: "<checksum>-<N>"` |
| `size=N` on a **non-image** (authorized) | the original bytes, unchanged |
| `size=N`, N **not** configured | broken-image placeholder (see below) |
| unknown id / deleted / **unauthorized**, with `size` present | broken-image placeholder |
| `?type=video` on an unresolvable request | video placeholder |
| unresolvable, **no** `size`/`type` (non-media) | uniform `404 file.not_found` |

**Success (sized image)** — `200 OK`
```
HTTP/1.1 200 OK
Content-Type: image/jpeg
Cache-Control: public, max-age=31536000, immutable      # private objects → "private, no-store"
ETag: "9f2c…-300"
Accept-Ranges: bytes
Content-Length: 18342

<resized JPEG bytes>
```

**Placeholder (unresolvable image/video)** — **`200 OK`** (NOT 404, so the browser renders our asset in `<img>`)
```
HTTP/1.1 200 OK
Content-Type: image/png
Cache-Control: no-store

<broken-image PNG bytes>      # identical whether the object is missing OR the caller is unauthorized (no existence leak)
```

**Non-media not-found** — `404 Not Found` (unchanged from the parent story)
```json
{
  "error": {
    "code": "file.not_found",
    "message": "file not found",
    "request_id": "req_01HZY…"
  }
}
```

### Config (backend, `env.yaml`)
```yaml
storage:
  image_sizes: [100, 300, 1000]   # configured bounding boxes (longest edge); others → placeholder
  derivative_ttl_hours: 168       # sliding cache TTL per (object, size); refreshed on every (re)generation/hit
```

## Implementation notes (delivered)
- **Migration `00023`** — `file_derivative` (lightweight cache record per `(public_id, size)`;
  GCS objects under the recognizable `derivatives/` prefix; sliding `expires_at`).
- **domain/file** — `Derivative`, `FitWithin` (pure fit-in-box, no upscale), `IsResizableImage`/
  `IsVideo`, `DerivativeObjectPath`, `DerivativeRepository` + `ImageProcessor` ports.
- **resource/imaging** — the ONLY package decoding/rescaling bytes (`golang.org/x/image/draw`
  CatmullRom + `x/image/webp` decode; stdlib jpeg/png encode).
- **usecase/file** — `ResolveSized` (resolve → authorize → cache hit/miss → regenerate),
  `SweepExpiredDerivatives`; `Delete` now clears derivatives. Background `derivative-sweeper`
  (hourly, via `safego`) clears lapsed variants.
- **resource/http/handler** — `Serve` parses `?size=`, renders the generated broken-image /
  video placeholders (200 + `no-store`) on unresolvable image/video requests.
