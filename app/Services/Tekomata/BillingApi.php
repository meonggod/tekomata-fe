<?php

namespace App\Services\Tekomata;

/**
 * Unified billing / charging endpoints of the Go API. The charging engine itself
 * is in-process on the backend; the panel only reads the explainable, line-itemed
 * charge history it records.
 *
 * Tenant-scoped by the JWT (also reachable via per-company API key on the API
 * side, but the panel always uses the session token). Money values are decimal
 * strings (IDR) on the wire — never floats.
 */
class BillingApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Cost breakdown over a period: the total, the split by kind (usage /
     * subscription / feature / ai), and the underlying line-itemed charges.
     *
     * @return array<string,mixed>
     */
    public function charges(string $token, string $from, string $to): array
    {
        return $this->client->get(
            '/api/v1/billing/charges',
            ['from' => $from, 'to' => $to],
            $token,
        )['data'] ?? [];
    }
}
