<?php

namespace App\Services\Tekomata;

/**
 * CS feature-assistant endpoint of the Go API — grounded answers about
 * tekomata's own features/pricing, from a tekomata-owned knowledge base.
 *
 * Public with an OPTIONAL JWT: on the homepage there's no tenant (AI cost is
 * tekomata's own); in-app the owner's token attributes the question to their
 * company. Every question is captured upstream for later review — distinct from
 * the tenant-facing web-chat widget (that sits on customers' sites).
 */
class CsApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Ask a plain-language question. `surface` is where it was asked
     * (homepage | in_app) so the backend can log it per surface. Pass the
     * caller's access token in-app to attribute the question; null on the public
     * surface. Returns `{ answer, answered, confidence }`.
     *
     * @return array<string,mixed>
     */
    public function ask(string $question, string $surface, ?string $token = null): array
    {
        return $this->client->post(
            '/api/v1/cs/ask',
            ['question' => $question, 'surface' => $surface],
            $token,
        )['data'] ?? [];
    }
}
