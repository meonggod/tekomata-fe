<?php

namespace App\Services\Tekomata\Admin;

use App\Services\Tekomata\TekomataClient;

/**
 * AI provider & model-catalog admin endpoints, behind the staff guard
 * (`/api/v1/internal/ai/*`). Staff JWT, never a tenant token. Providers and their
 * credentials are tekomata-owned; the model catalog carries per-model pricing and
 * the `is_active` / `user_selectable` flags the charging + selection paths gate on.
 *
 * Provider credentials are WRITE-ONLY: reads expose only `has_credential`, never
 * the secret. Auto-discovered models land in a pending-review queue (`status=
 * pending`, `priced=false`) and must be priced + activated before they can bill.
 */
class AiCatalogApi
{
    public function __construct(private readonly TekomataClient $client) {}

    // ---- Providers ----------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function providers(string $token): array
    {
        return $this->client->get('/api/v1/internal/ai/providers', [], $token)['data']['providers'] ?? [];
    }

    /**
     * Register a provider (credential is stored write-only).
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function createProvider(string $token, array $data): array
    {
        return $this->client->post('/api/v1/internal/ai/providers', $data, $token)['data'] ?? [];
    }

    /**
     * Update a provider — credential (write-only) and/or priority/fallback order.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateProvider(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/internal/ai/providers/'.$id, $data, $token)['data'] ?? [];
    }

    // ---- Model catalog ------------------------------------------------------

    /**
     * The model catalog. `status` filters the list (e.g. `pending` for the
     * review queue).
     *
     * @param  array<string,mixed>  $query
     * @return array<int,array<string,mixed>>
     */
    public function models(string $token, array $query = []): array
    {
        return $this->client->get('/api/v1/internal/ai/models', $query, $token)['data']['models'] ?? [];
    }

    /**
     * Price + activate a model (clears it from the pending-review queue).
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function priceModel(string $token, string $id, array $data): array
    {
        return $this->client->post('/api/v1/internal/ai/models/'.$id.'/price', $data, $token)['data'] ?? [];
    }

    /**
     * Toggle a model's platform flags (`is_active`, `user_selectable`).
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateModel(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/internal/ai/models/'.$id, $data, $token)['data'] ?? [];
    }

    // ---- Sync ---------------------------------------------------------------

    /**
     * Trigger a manual catalog sync. Returns a run summary
     * `{added, removed, unchanged, failures}`.
     *
     * @return array<string,mixed>
     */
    public function sync(string $token): array
    {
        return $this->client->post('/api/v1/internal/ai/sync', [], $token)['data'] ?? [];
    }
}
