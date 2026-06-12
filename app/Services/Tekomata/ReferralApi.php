<?php

namespace App\Services\Tekomata;

/**
 * Company referral endpoints of the Go API. The code is issued lazily on first
 * view, so a GET is all the panel needs — there's no explicit "create" call.
 *
 * Tenant-scoped by the JWT: the code belongs to the active company carried in
 * the token. Reward totals are decimal strings (IDR) — they accrue into the
 * company's withdrawable reward wallet, surfaced on the wallet page.
 */
class ReferralApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * The company's referral code, share link, total reward earned, and the list
     * of companies it has referred (with each one's reward state).
     *
     * @return array<string,mixed>
     */
    public function overview(string $token): array
    {
        return $this->client->get('/api/v1/referral', [], $token)['data'] ?? [];
    }
}
