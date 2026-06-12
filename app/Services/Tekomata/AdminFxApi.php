<?php

namespace App\Services\Tekomata;

/**
 * Platform-admin FX-rate endpoints of the Go API (tekomata-internal, NOT
 * tenant-scoped). Authenticated with the `X-Admin-Key` header rather than a JWT
 * — the key is tekomata-owned (config), never per-company.
 *
 * Rates are decimal strings; `fetched_at` is a unix-ms timestamp; `stale` flags a
 * rate older than the backend's configured max age. Only mounted on the backend
 * when the admin key is configured.
 */
class AdminFxApi
{
    public function __construct(
        private readonly TekomataClient $client,
        private readonly ?string $adminKey,
    ) {}

    /** Whether the admin key is configured; if not, the admin calls can't be made. */
    public function configured(): bool
    {
        return $this->adminKey !== null && $this->adminKey !== '';
    }

    /**
     * Current rate per pair + freshness.
     *
     * @return array<int,array<string,mixed>>
     */
    public function rates(): array
    {
        return $this->client->get('/api/v1/admin/fx/rates', [], null, $this->headers())['data']['rates'] ?? [];
    }

    /**
     * Force an immediate refresh. Returns `{ synced, rates }`. A 500
     * `fx.sync_unavailable` means the provider isn't configured upstream.
     *
     * @return array<string,mixed>
     */
    public function sync(): array
    {
        return $this->client->post('/api/v1/admin/fx/sync', [], null, $this->headers())['data'] ?? [];
    }

    /**
     * @return array<string,string>
     */
    private function headers(): array
    {
        return $this->adminKey !== null && $this->adminKey !== ''
            ? ['X-Admin-Key' => $this->adminKey]
            : [];
    }
}
