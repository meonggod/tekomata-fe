<?php

namespace App\Services\Tekomata;

/**
 * Onboarding endpoints of the Go API. One thin method per call.
 */
class OnboardingApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Returns the current onboarding gate state — whether the active company is
     * gated and which KYC/KYB fields are still missing.
     *
     * @return array<string,mixed>
     */
    public function requirements(string $token): array
    {
        return $this->client->get('/api/v1/onboarding/requirements', token: $token)['data'] ?? [];
    }

    /**
     * Submit (upsert) the user's KYC personal identity. Idempotent.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function submitKyc(string $token, array $data): array
    {
        return $this->client->post('/api/v1/onboarding/kyc', $data, $token)['data'] ?? [];
    }

    /**
     * Submit (upsert) the active company's KYB business profile. Idempotent.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function submitKyb(string $token, array $data): array
    {
        return $this->client->post('/api/v1/onboarding/kyb', $data, $token)['data'] ?? [];
    }
}
