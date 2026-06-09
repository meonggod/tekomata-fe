<?php

namespace App\Services\Tekomata;

/**
 * Category CRUD, product grouping, and product-side category assignment
 * on the Go API. All calls are JWT-scoped; the active company is resolved
 * from the token.
 */
class CategoryApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<int,array<string,mixed>> */
    public function list(string $token, ?string $search = null): array
    {
        $query = $search !== null && $search !== '' ? ['search' => $search] : [];

        return $this->client->get('/api/v1/categories', $query, $token)['data']['categories'] ?? [];
    }

    /** @return array<string,mixed> */
    public function get(string $token, string $id): array
    {
        return $this->client->get('/api/v1/categories/'.rawurlencode($id), token: $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function create(string $token, array $data): array
    {
        return $this->client->post('/api/v1/categories', $data, $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function update(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/categories/'.rawurlencode($id), $data, $token)['data'] ?? [];
    }

    /** Archives the category and removes all its groupings; products are kept. */
    public function delete(string $token, string $id): void
    {
        $this->client->delete('/api/v1/categories/'.rawurlencode($id), token: $token);
    }

    /** Products grouped under a category. @return array<int,array<string,mixed>> */
    public function products(string $token, string $id): array
    {
        return $this->client->get('/api/v1/categories/'.rawurlencode($id).'/products', token: $token)['data']['products'] ?? [];
    }

    /** Idempotently add products to a category (POST /categories/{id}/products). */
    public function addProducts(string $token, string $id, array $productIds): void
    {
        $this->client->post('/api/v1/categories/'.rawurlencode($id).'/products', ['product_ids' => $productIds], $token);
    }

    /** Idempotently remove one product from a category. */
    public function removeProduct(string $token, string $id, string $productId): void
    {
        $this->client->delete(
            '/api/v1/categories/'.rawurlencode($id).'/products/'.rawurlencode($productId),
            token: $token,
        );
    }

    /** Categories currently assigned to a product. @return array<int,array<string,mixed>> */
    public function productCategories(string $token, string $productId): array
    {
        return $this->client->get('/api/v1/products/'.rawurlencode($productId).'/categories', token: $token)['data']['categories'] ?? [];
    }

    /**
     * Replace the full set of categories for a product (PUT /products/{id}/categories).
     *
     * @return array<int,array<string,mixed>>
     */
    public function setProductCategories(string $token, string $productId, array $categoryIds): array
    {
        return $this->client->put(
            '/api/v1/products/'.rawurlencode($productId).'/categories',
            ['category_ids' => $categoryIds],
            $token,
        )['data']['categories'] ?? [];
    }
}
