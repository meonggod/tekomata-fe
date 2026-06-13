<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\ProductMediaApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Same-origin JSON proxy for the product media gallery. The product-edit page's
 * media manager (vanilla JS) calls these so the session JWT is attached
 * server-side and never reaches the browser. The Go API stays the source of
 * truth and enforces the real limits; the checks here are first-line feedback.
 *
 * Returns small JSON envelopes ({ data } | { error, request_id? }) the manager
 * renders. Mutations re-fetch the gallery client-side, so each handler returns
 * the upstream result as-is.
 */
class ProductMediaController extends Controller
{
    /** Allowed image content types → per-format cap in bytes (20 MB). */
    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Allowed video content types → per-format cap in bytes (100 MB). */
    private const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

    private const IMAGE_MAX_BYTES = 20 * 1024 * 1024;

    private const VIDEO_MAX_BYTES = 100 * 1024 * 1024;

    private const VIEWS = ['front', 'back', 'left', 'right', 'top', 'bottom', 'detail'];

    public function __construct(
        private readonly ProductMediaApi $media,
        private readonly TokenStore $tokens,
    ) {}

    /** List the product's gallery. */
    public function index(string $id): JsonResponse
    {
        try {
            $gallery = $this->media->list((string) $this->tokens->accessToken(), $id);
        } catch (ApiUnavailableException $e) {
            return $this->failUnavailable($e);
        } catch (TekomataApiException $e) {
            return response()->json(['error' => $e->localizedMessage()], $e->status >= 400 ? $e->status : 400);
        }

        return response()->json(['data' => $gallery]);
    }

    /** Upload one photo (with a view) or one video. */
    public function store(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
            'view' => ['nullable', 'string', 'in:'.implode(',', self::VIEWS)],
            'is_thumbnail' => ['nullable'],
        ]);

        $file = $request->file('file');
        $type = $file->getMimeType();
        $isImage = in_array($type, self::IMAGE_TYPES, true);
        $isVideo = in_array($type, self::VIDEO_TYPES, true);

        if (! $isImage && ! $isVideo) {
            return response()->json(['error' => __('errors.product_media.unsupported_format')], 422);
        }

        $cap = $isImage ? self::IMAGE_MAX_BYTES : self::VIDEO_MAX_BYTES;
        if ($file->getSize() > $cap) {
            return response()->json(['error' => __('errors.product_media.too_large')], 400);
        }

        $fields = [];
        if ($isImage) {
            // A photo needs a view; a video must not carry one.
            if (! in_array($request->input('view'), self::VIEWS, true)) {
                return response()->json(['error' => __('errors.validation.required')], 422);
            }
            $fields['view'] = $request->input('view');
            if ($request->boolean('is_thumbnail')) {
                $fields['is_thumbnail'] = '1';
            }
        }

        try {
            $item = $this->media->upload(
                (string) $this->tokens->accessToken(),
                $id,
                (string) file_get_contents($file->getRealPath()),
                $file->getClientOriginalName() ?: 'upload',
                $fields,
            );
        } catch (ApiUnavailableException $e) {
            return $this->failUnavailable($e);
        } catch (TekomataApiException $e) {
            return response()->json(['error' => $e->localizedMessage()], $e->status >= 400 ? $e->status : 422);
        }

        return response()->json(['data' => $item], 201);
    }

    /** Reorder the gallery to the supplied id sequence. */
    public function reorder(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['string'],
        ]);

        try {
            $gallery = $this->media->reorder(
                (string) $this->tokens->accessToken(),
                $id,
                $validated['media_ids'],
            );
        } catch (ApiUnavailableException $e) {
            return $this->failUnavailable($e);
        } catch (TekomataApiException $e) {
            return response()->json(['error' => $e->localizedMessage()], $e->status >= 400 ? $e->status : 400);
        }

        return response()->json(['data' => $gallery]);
    }

    /** Mark one photo as the product thumbnail. */
    public function thumbnail(string $id, string $mediaId): JsonResponse
    {
        try {
            $gallery = $this->media->setThumbnail((string) $this->tokens->accessToken(), $id, $mediaId);
        } catch (ApiUnavailableException $e) {
            return $this->failUnavailable($e);
        } catch (TekomataApiException $e) {
            return response()->json(['error' => $e->localizedMessage()], $e->status >= 400 ? $e->status : 400);
        }

        return response()->json(['data' => $gallery]);
    }

    /** Remove a media item (releases its stored object upstream). */
    public function destroy(string $id, string $mediaId): JsonResponse
    {
        try {
            $this->media->delete((string) $this->tokens->accessToken(), $id, $mediaId);
        } catch (ApiUnavailableException $e) {
            return $this->failUnavailable($e);
        } catch (TekomataApiException $e) {
            return response()->json(['error' => $e->localizedMessage()], $e->status >= 400 ? $e->status : 400);
        }

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function failUnavailable(ApiUnavailableException $e): JsonResponse
    {
        return response()->json([
            'error' => __('errors.generic'),
            'request_id' => $e->requestId(),
        ], 503);
    }
}
