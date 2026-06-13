<?php

namespace App\Services\Tekomata;

/**
 * Product media gallery on the Go API — photos (view-tagged) and videos,
 * one designated thumbnail, ordered for display. Every call is JWT-scoped;
 * the active company is resolved from the token, never the request.
 *
 * Media bytes flow through the same shipped private-file-storage GCS layer;
 * the API returns each item's branded storage-proxy `url` (never a raw bucket
 * link), which the panel renders with an on-the-fly `?size=` variant.
 */
class ProductMediaApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * The product's gallery: `{ media: [...], thumbnail: {...}|null }`,
     * items ordered by `sort_order`.
     *
     * @return array<string,mixed>
     */
    public function list(string $token, string $productId): array
    {
        return $this->client->get('/api/v1/products/'.rawurlencode($productId).'/media', [], $token)['data'] ?? [];
    }

    /**
     * Upload a single photo (with its `view`, optionally the thumbnail) or a
     * video (no view). `kind` is inferred upstream from the content type.
     *
     * @param  array<string,scalar>  $fields  view / is_thumbnail (photos only).
     * @return array<string,mixed> The created media item.
     */
    public function upload(string $token, string $productId, string $fileContents, string $fileName, array $fields): array
    {
        $data = $this->client->postMultipart(
            '/api/v1/products/'.rawurlencode($productId).'/media',
            'file',
            $fileContents,
            $fileName,
            $fields,
            $token,
        );

        return $data['data'] ?? $data;
    }

    /**
     * Reorder the gallery — `sort_order` becomes each id's position. Returns
     * the reordered gallery (same shape as {@see list()}).
     *
     * @param  array<int,string>  $mediaIds
     * @return array<string,mixed>
     */
    public function reorder(string $token, string $productId, array $mediaIds): array
    {
        return $this->client->put(
            '/api/v1/products/'.rawurlencode($productId).'/media/reorder',
            ['media_ids' => array_values($mediaIds)],
            $token,
        )['data'] ?? [];
    }

    /**
     * Mark one photo as the product's thumbnail; clears the prior one upstream.
     *
     * @return array<string,mixed> The updated gallery.
     */
    public function setThumbnail(string $token, string $productId, string $mediaId): array
    {
        return $this->client->post(
            '/api/v1/products/'.rawurlencode($productId).'/media/'.rawurlencode($mediaId).'/thumbnail',
            [],
            $token,
        )['data'] ?? [];
    }

    /**
     * Remove a media item; releases its underlying stored object upstream.
     */
    public function delete(string $token, string $productId, string $mediaId): void
    {
        $this->client->delete(
            '/api/v1/products/'.rawurlencode($productId).'/media/'.rawurlencode($mediaId),
            [],
            $token,
        );
    }
}
