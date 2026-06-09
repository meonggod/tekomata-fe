<?php

namespace App\Services\Tekomata;

/**
 * Warehouse CRUD on the Go API.
 * All calls are JWT-scoped; the active company is resolved from the token.
 */
class WarehouseApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<int,array<string,mixed>> */
    public function list(string $token): array
    {
        return $this->client->get('/api/v1/warehouses', token: $token)['data']['warehouses'] ?? [];
    }

    /** @return array<string,mixed> */
    public function get(string $token, string $id): array
    {
        return $this->client->get('/api/v1/warehouses/'.rawurlencode($id), token: $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function create(string $token, array $data): array
    {
        return $this->client->post('/api/v1/warehouses', $data, $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function update(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/warehouses/'.rawurlencode($id), $data, $token)['data'] ?? [];
    }

    public function delete(string $token, string $id): void
    {
        $this->client->delete('/api/v1/warehouses/'.rawurlencode($id), token: $token);
    }
}
