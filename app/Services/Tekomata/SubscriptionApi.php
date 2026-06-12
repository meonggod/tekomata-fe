<?php

namespace App\Services\Tekomata;

/**
 * Subscription plan + company-subscription endpoints of the Go API. One thin
 * method per call.
 *
 * Every path is tenant-scoped by the JWT — the active company rides inside the
 * token, never the request. Money values (monthly_price, base_rate) are decimal
 * strings (IDR) on the wire and in the rendered panel — never floats.
 *
 * Plan CRUD is tekomata-admin only (X-Admin-Key) and is NOT this repo's concern;
 * we only consume the company-facing surface an owner uses to view + buy a plan.
 */
class SubscriptionApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * The catalogue of purchasable plans (active only, as the API returns them).
     *
     * @return array<int,array<string,mixed>>
     */
    public function plans(string $token): array
    {
        return $this->client->get('/api/v1/subscription/plans', [], $token)['data']['plans'] ?? [];
    }

    /**
     * The company's current subscription state. Free Tier 0 is represented as
     * { active:false, base_rate } with no `subscription` object.
     *
     * @return array<string,mixed>
     */
    public function current(string $token): array
    {
        return $this->client->get('/api/v1/subscription', [], $token)['data'] ?? [];
    }

    /**
     * Buy / switch to a plan — debits the monthly price from the spendable
     * wallet. Throws on insufficient balance (409 subscription.insufficient_balance)
     * or an unknown plan (404 subscription.plan_not_found).
     *
     * @return array<string,mixed>
     */
    public function subscribe(string $token, string $planId): array
    {
        return $this->client->post('/api/v1/subscription/subscribe', ['plan_id' => $planId], $token)['data'] ?? [];
    }

    /**
     * Turn off auto-renew — the plan stays active until the period ends, then
     * lapses back to free Tier 0.
     *
     * @return array<string,mixed>
     */
    public function cancel(string $token): array
    {
        return $this->client->post('/api/v1/subscription/cancel', [], $token)['data'] ?? [];
    }
}
