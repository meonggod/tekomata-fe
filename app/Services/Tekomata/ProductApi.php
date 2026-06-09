<?php

namespace App\Services\Tekomata;

/**
 * Products, per-warehouse stock, and movement log on the Go API.
 * All calls are JWT-scoped; the active company is resolved from the token.
 */
class ProductApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<int,array<string,mixed>> */
    public function list(string $token, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $query = ['limit' => $limit, 'offset' => $offset];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->client->get('/api/v1/products', $query, $token)['data']['products'] ?? [];
    }

    /** Product detail including per-warehouse stock balances. @return array<string,mixed> */
    public function get(string $token, string $id): array
    {
        return $this->client->get('/api/v1/products/'.rawurlencode($id), token: $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function create(string $token, array $data): array
    {
        return $this->client->post('/api/v1/products', $data, $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function update(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/products/'.rawurlencode($id), $data, $token)['data'] ?? [];
    }

    public function delete(string $token, string $id): void
    {
        $this->client->delete('/api/v1/products/'.rawurlencode($id), token: $token);
    }

    /**
     * Append a stock movement (quantity delta) for a product in a warehouse.
     * Returns `{movement, balance}`.
     *
     * @return array<string,mixed>
     */
    public function adjustStock(string $token, string $id, array $data): array
    {
        return $this->client->post('/api/v1/products/'.rawurlencode($id).'/stock', $data, $token)['data'] ?? [];
    }

    /**
     * Stock movement log for a product, newest first.
     * Filters: warehouse_id, from (epoch-ms), to (epoch-ms), limit, offset.
     *
     * @return array<int,array<string,mixed>>
     */
    public function movements(string $token, string $id, array $filters = []): array
    {
        return $this->client->get('/api/v1/products/'.rawurlencode($id).'/movements', $filters, $token)['data']['movements'] ?? [];
    }
}
