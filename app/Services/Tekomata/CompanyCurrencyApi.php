<?php

namespace App\Services\Tekomata;

/**
 * The active company's enabled-currency subset on the Go API. Tenant-scoped:
 * every call carries the caller's bearer token and the API resolves the active
 * company from the JWT — never from the request. The shared catalog itself
 * (every active currency) lives in CatalogApi; this is the per-company switch
 * on top of it (which ones are enabled, and which single one is the default).
 */
class CompanyCurrencyApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * The active company's enabled currencies — catalog fields plus `is_default`.
     *
     * @return array<int,array<string,mixed>>
     */
    public function enabled(string $accessToken): array
    {
        return $this->client->get('/api/v1/company/currencies', token: $accessToken)['data']['currencies'] ?? [];
    }

    /**
     * Enable a currency for the active company. The first one enabled becomes
     * the default automatically. Errors: `400 catalog.currency_not_active`,
     * `409 company.currency_already_enabled`.
     *
     * @return array<string,mixed>
     */
    public function enable(string $accessToken, string $code): array
    {
        return $this->client->post('/api/v1/company/currencies', ['currency_code' => $code], $accessToken)['data'] ?? [];
    }

    /**
     * Mark an already-enabled currency as the company default (exactly one).
     * Error: `404 company.currency_not_enabled`.
     *
     * @return array<string,mixed>
     */
    public function setDefault(string $accessToken, string $code): array
    {
        return $this->client->put('/api/v1/company/currencies/'.rawurlencode($code).'/default', token: $accessToken)['data'] ?? [];
    }

    /**
     * Disable a currency for the active company. The current default cannot be
     * disabled. Errors: `404 company.currency_not_enabled`,
     * `409 company.cannot_disable_default_currency`.
     */
    public function disable(string $accessToken, string $code): void
    {
        $this->client->delete('/api/v1/company/currencies/'.rawurlencode($code), token: $accessToken);
    }
}
