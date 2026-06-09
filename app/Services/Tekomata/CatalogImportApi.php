<?php

namespace App\Services\Tekomata;

/**
 * Catalog import (bulk upsert) and catalog browse for the active company.
 * All calls are JWT-scoped; the active company is resolved from the token.
 */
class CatalogImportApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Bulk-upsert products, warehouses, stock, and price tiers.
     * Returns the summary + per-row errors from the Go API.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<string,mixed>
     */
    public function import(string $token, array $rows): array
    {
        return $this->client->post('/api/v1/catalog/import', ['rows' => $rows], $token)['data'] ?? [];
    }

    /**
     * List products with per-warehouse stock and per-tier prices.
     *
     * @return array<int,array<string,mixed>>
     */
    public function browse(string $token, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $query = ['limit' => $limit, 'offset' => $offset];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->client->get('/api/v1/catalog', $query, $token)['data']['products'] ?? [];
    }
}
