---
name: '[STORY] Product photo & video gallery with angle-tagged shots and a thumbnail'
priority: 3
status: done
tags:
    - catalog
    - product
    - media
    - storage
---

A product can carry a gallery of media — multiple photos and videos — where each photo is tagged
with the view it shows (front, back, left, right, top, bottom, detail, …) and one image is marked
as the product's thumbnail. The owner manages this gallery from the control panel; the structured,
angle-tagged shots also give the vision AI clean, labelled imagery to reason over.

## Backend
**Goal:** model product media as a typed, ordered collection attached to a product, uploaded
through the existing private storage proxy, with a photo view-angle, a media kind (photo/video),
and a single designated thumbnail per product.

### Product media model + migration — _engineer_
- **Goal:** persist a product's media items: kind (`photo`/`video`), the stored object reference
(the opaque storage id, never a raw GCS URL), photo `view` angle, an `is_thumbnail` flag, and a
sort order for display.
- **Background:** extends the existing `crud-product` catalog schema; media is company-scoped and
tenant-isolated like the rest of the catalog. Objects live in the private GCS bucket and are
served only via the `[storage.tekomata.com/](http://storage.tekomata.com/)<opaque-id>` proxy from `private-file-storage` — store
the opaque id, not a public URL. The uploaded image is kept **pristine**; display sizes are fetched
on the fly from the storage proxy (see `on-the-fly-image-sizing`), so this story stores no resized
copies. The `view` set (front, back, left, right, top, bottom, detail,
…) should be an extensible enum, and applies to photos only; videos carry no view. Each
**single-angle** view (front/back/left/right/top/bottom) holds **at most one** photo; `detail` is
the multi-slot bucket for extra close-ups. At most one media item per product may have
`is_thumbnail = true`, and **the thumbnail must be a photo, never a video**. A video has no
resizable still, so the gallery represents each video with a shared default **black placeholder
image bearing a video icon** (one static asset, not a per-video generated poster) so it's visually
marked as a video.

### Product media endpoints (upload / list / reorder / set-thumbnail / delete) — _engineer_
- **Goal:** let a product's gallery be managed over the API — upload a photo (with its view) or a
video, list a product's media, reorder it, mark one image as the thumbnail, and remove an item.
- **Background:** API-first so both the Laravel panel (user JWT) and per-company API keys reach it,
and every call stays scoped to the active company. Uploads go through the **same shipped
`private-file-storage` GCS layer** as the rest of the product/catalog uploads — no separate bucket.
Deleting a media item should release its stored object. Setting a new thumbnail clears the prior
one so the single-thumbnail rule holds. **Enforce per-product media limits:** at most **5 videos**
per product; each single-angle photo view (front/back/left/right/top/bottom) **at most 1**; up to
**10 `detail` photos**; the thumbnail and all photo slots must be images, never videos.

### Per-format upload size caps — _ai-agent_
- **Goal:** cap each uploaded file by its format with a **configurable per-extension max size**, so a
format's limit can be tuned without a redeploy.
- **Background:** extends the shipped `private-file-storage` content-type allowlist + max-size check
to a **per-format** limit (config-driven, DB-backed like tekomata's other config). Allowed formats:
**images JPEG/PNG/WebP — 20 MB each; videos MP4/WebM/MOV — 100 MB each** (GIF is not accepted). An
upload exceeding its format's cap (or any media limit above) is rejected with a clear error.
Because videos are large,
they should use the storage layer's **direct-to-GCS signed-upload path** rather than streaming the
bytes through the Go API (the scale path the parent storage story already anticipated for large
files); small images can keep flowing through the API.

### Thumbnail resolution helper — _ai-agent_
- **Goal:** centralize "what image represents this product" so list/lookup responses can surface a
thumbnail consistently.
- **Background:** returns the item flagged `is_thumbnail`, falling back to the first photo by sort
order when none is flagged. Keep it a small pure function over a product's media set.

## Frontend
**Goal:** give the owner a product media manager in the control panel — upload photos tagged by
view, upload videos, pick the thumbnail, reorder, and delete.

### Product media manager — _engineer_
- **Goal:** capture and manage a product's gallery from the product edit screen.
- **Background:** lives in the existing Laravel product CRUD pages and calls the Go media
endpoints. The user picks a view when uploading a photo, can upload videos, marks one image as
the thumbnail, reorders items, and deletes them. Render media through the storage proxy URLs the
API returns — never raw bucket links — requesting an appropriately sized variant for thumbnails vs.
full view (via `on-the-fly-image-sizing`) rather than the full original. A video tile shows the
default black-with-video-icon placeholder (videos aren't resized).

## Acceptance criteria
- [x] A product can hold multiple photos and multiple videos at once.
- [x] Each photo is tagged with a view angle (front, back, left, right, top, bottom, detail, …);
videos are stored without a view.
- [x] Each single-angle view (front/back/left/right/top/bottom) holds at most one photo; `detail`
may hold up to 10; a product accepts at most 5 videos. Exceeding any limit is rejected with a
clear error.
- [x] Exactly one media item can be marked as the product's thumbnail, and the thumbnail must be a
photo (never a video); setting a new thumbnail clears the previous one.
- [x] Allowed formats are images JPEG/PNG/WebP (20 MB each) and videos MP4/WebM/MOV (100 MB each),
GIF not accepted; the per-format caps are configurable and an over-size or disallowed-format
upload is rejected with a clear error.
- [x] A video is represented in the gallery by the shared default black-with-video-icon placeholder
(videos are not resized and have no generated poster).
- [x] When no thumbnail is set, the product resolves a sensible default (first photo by order).
- [x] Media is uploaded, listed, reordered, and deleted through the API, scoped to the active
company, and served only via the private storage proxy (no raw GCS URLs).
- [x] Product media uses the same shipped `private-file-storage` GCS layer (no separate bucket).
- [x] Deleting a media item removes its underlying stored object.
- [x] The uploaded image is kept pristine; thumbnails and previews are fetched as on-the-fly sized
variants from the storage proxy, not stored as separate resized copies by this story.
- [x] The owner can perform all of the above (upload photo with a view, upload a video, set the
thumbnail, reorder, delete) from the product page in the Laravel control panel. _(Frontend — Laravel repo)_

> **Frontend (this repo) — done.** Product media manager on the product edit page
> (`resources/views/products/partials/media-manager.blade.php`, included in `products/edit.blade.php`):
> photo upload with a view-angle picker + "set as thumbnail", video upload, drag-to-reorder,
> per-tile "make thumbnail" + remove, and a video tile rendered with the shared
> black-with-video-icon placeholder (`public/img/video-placeholder.svg`). Thumbnails/previews are
> requested as on-the-fly `?size=` variants off the storage-proxy `url` the API returns — never a
> raw bucket link. All calls go same-origin through `ProductMediaController` → `ProductMediaApi`
> (JWT attached server-side, never exposed to the browser); JS is `initProductMedia()` in
> `resources/js/app.js`. Per-format/size caps are validated client-side for fast feedback; the Go
> API stays authoritative. Error codes (`product_media.*`, `file.storage_unavailable`) localized in
> `lang/{en,id}/errors.php`; UI strings in `lang/{en,id}/messages.php` (`products.media.*`).

---

## API contract — frontend handoff (build in parallel)

All routes JWT-only, tenant-scoped via the Principal (`company_id` from the token,
never the body). Media bytes flow through the **same** shipped `private-file-storage`
GCS layer (uploaded `public`, served via the branded proxy — never a raw GCS URL).
Postman: folder "Products / Media". Mermaid + design: `documentation/features/product-media-gallery.md`.
Display sizes are fetched on the fly via `?size=` (feature: `on-the-fly-image-sizing`).

### `POST /api/v1/products/{id}/media` — upload a photo or video (multipart/form-data)
Fields: `file` (required), `view` (required for photos: front|back|left|right|top|bottom|detail),
`is_thumbnail` (optional, photos only). `kind` is inferred from the content type
(image/* → photo, video/* → video).

**Per-format caps (config `storage.media_formats`, changeable without redeploy):**
images **JPEG/PNG/WebP ≤ 20 MB**, videos **MP4/WebM/MOV ≤ 100 MB**. GIF is rejected.
**Limits:** each single-angle view ≤ 1 photo, `detail` ≤ 10 photos, ≤ 5 videos/product.

**Success** — `201 Created`
```json
{ "data": {
  "id": "7c2e…", "kind": "photo", "view": "front", "is_thumbnail": false,
  "sort_order": 0, "content_type": "image/jpeg",
  "url": "https://storage.tekomata.com/files/u3Jq8x2pQF6m0aZ1bC9dEg",
  "created_at": 1749800000000, "updated_at": 1749800000000
} }
```
The FE renders thumbnails/previews by appending `?size=` to `url` (e.g. `…dEg?size=300`).
A **video** tile shows the FE's shared black-with-video-icon placeholder (videos are
not resized and have no generated poster).

**Errors**
```json
{ "error": { "code": "product_media.unsupported_format", "message": "this media format is not allowed",
  "request_id": "req_…", "fields": [ { "field": "file", "code": "validation.unsupported_file_type" } ] } }
```
| status | code | when |
|---|---|---|
| 422 | `product_media.unsupported_format` | content type not in the allowlist (e.g. GIF) |
| 400 | `product_media.too_large` | file exceeds its format's cap |
| 422 | `validation_failed` (`fields[view]`) | photo missing a view / a video carrying a view |
| 409 | `product_media.view_taken` | a single-angle view already has a photo |
| 409 | `product_media.limit_exceeded` | > 5 videos or > 10 `detail` photos |
| 404 | `product.not_found` | product not owned by the active company |
| 500 | `file.storage_unavailable` | storage backend not configured |

### `GET /api/v1/products/{id}/media` — list the gallery
`200 { "data": { "media": [ …items… ], "thumbnail": {…}|null } }` — items ordered by
`sort_order`; `thumbnail` is the resolved representative (flagged item, else first photo).

### `PUT /api/v1/products/{id}/media/reorder` — reorder
Body `{ "media_ids": ["id1","id2",…] }` → sets `sort_order` = position. Every id must
belong to the product (else `404 product_media.not_found`, whole reorder rolls back).
Returns the reordered gallery (same shape as list).

### `POST /api/v1/products/{id}/media/{mediaId}/thumbnail` — set the thumbnail
Clears the prior thumbnail and flags this item. The target must be a **photo**
(`422 product_media.thumbnail_must_be_photo` for a video). Returns the gallery.

### `DELETE /api/v1/products/{id}/media/{mediaId}` — remove an item
Soft-deletes the row and releases the underlying GCS object. Idempotent `204`.
`404 product_media.not_found` when absent / not owned.

Also: `GET /api/v1/products/{id}` now includes `"thumbnail": { "id", "view", "url" }|null`.

## Implementation notes (delivered)
- **Migration `00024`** — `product_media` (kind/view/is_thumbnail/sort_order, FK to `file`);
  partial-unique indexes enforce **≤1 photo per single-angle view** and **≤1 thumbnail**;
  the ≤5-video / ≤10-detail caps are count-based in the usecase.
- **domain/catalog** — `ProductMedia`, `MediaKind`/`MediaView` (extensible text enums),
  `KindForContentType`, pure `ResolveThumbnail`, `ProductMediaRepository` port.
- **usecase/catalog** — `ProductMediaUsecase` (`EnsureCanAdd` pre-upload gate, `Add`,
  `List`, `Thumbnail`, `SetThumbnail`, `Reorder`, `Delete`); imports domain only.
- **usecase/file** — new `StoreTrusted` (store bytes whose format/size the caller already
  validated) + `DeleteByPublicID`; the media bytes reuse the shipped GCS layer.
- **resource/http/handler** — `product_media.go` orchestrates the file service + media
  usecase; per-format caps + allowlist are config-driven (`storage.media_formats`).
- **Config** — `storage.media_formats` (content type → max bytes).

## Deferred (documented, not blocking acceptance)
- **Direct-to-GCS signed-upload path for videos** — videos currently stream through the API
  (cap-enforced ≤100 MB), matching the parent `private-file-storage` story, which likewise
  deferred signed URLs. The opaque-id + per-format-cap contract is unchanged when the
  signed-upload optimization lands.
