<?php

namespace App\Services\Tekomata\Admin;

use App\Services\Tekomata\TekomataClient;

/**
 * Billing & platform-config admin endpoints of the Go API, behind the staff guard
 * (`/api/v1/internal/*`). Authenticated with the caller's STAFF JWT — never a
 * tenant token, never the X-Admin-Key. Covers subscription plans, the feature
 * pricing catalog, promo codes, FX rates/sync, the platform settings bag
 * (payout min/fee, referral cap, FX staleness max-age), and the country/currency
 * platform-active flags.
 *
 * Every method is thin: shape the request, unwrap the `data` envelope. Reads are
 * open to any staff; writes are superadmin-only (enforced upstream + by the
 * `internal.superadmin` route middleware). The token is passed in by the
 * controller (from StaffTokenStore) so this service stays stateless.
 */
class PlatformConfigApi
{
    public function __construct(private readonly TekomataClient $client) {}

    // ---- Subscription plans -------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function plans(string $token): array
    {
        return $this->client->get('/api/v1/internal/subscription-plans', [], $token)['data']['plans'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function createPlan(string $token, array $data): array
    {
        return $this->client->post('/api/v1/internal/subscription-plans', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updatePlan(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/internal/subscription-plans/'.$id, $data, $token)['data'] ?? [];
    }

    // ---- Feature pricing ----------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function featurePrices(string $token): array
    {
        return $this->client->get('/api/v1/internal/feature-prices', [], $token)['data']['features'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateFeaturePrice(string $token, string $key, array $data): array
    {
        return $this->client->put('/api/v1/internal/feature-prices/'.$key, $data, $token)['data'] ?? [];
    }

    // ---- Promo codes --------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function promoCodes(string $token): array
    {
        return $this->client->get('/api/v1/internal/promo-codes', [], $token)['data']['promo_codes'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function createPromoCode(string $token, array $data): array
    {
        return $this->client->post('/api/v1/internal/promo-codes', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updatePromoCode(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/internal/promo-codes/'.$id, $data, $token)['data'] ?? [];
    }

    // ---- FX rates -----------------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function fxRates(string $token): array
    {
        return $this->client->get('/api/v1/internal/fx/rates', [], $token)['data']['rates'] ?? [];
    }

    /** @return array<string,mixed> */
    public function fxSync(string $token): array
    {
        return $this->client->post('/api/v1/internal/fx/sync', [], $token)['data'] ?? [];
    }

    // ---- Platform settings (payout / referral cap / FX max-age) -------------

    /**
     * The platform settings bag. Keys observed: `fx_max_age_hours`,
     * `wallet_payout_min`, `wallet_payout_fee`, `referral_reward_cap`.
     *
     * @return array<string,mixed>
     */
    public function platformSettings(string $token): array
    {
        return $this->client->get('/api/v1/internal/platform-settings', [], $token)['data']['settings'] ?? [];
    }

    /**
     * Update one setting key. The backend types-validates per key
     * (`PUT /internal/platform-settings/{key}`).
     *
     * @return array<string,mixed>
     */
    public function updatePlatformSetting(string $token, string $key, mixed $value): array
    {
        return $this->client->put('/api/v1/internal/platform-settings/'.$key, ['value' => $value], $token)['data'] ?? [];
    }

    // ---- Countries & currencies (platform-active flags) ---------------------

    /** @return array<int,array<string,mixed>> */
    public function countries(string $token): array
    {
        return $this->client->get('/api/v1/internal/countries', [], $token)['data']['countries'] ?? [];
    }

    /** @return array<int,array<string,mixed>> */
    public function currencies(string $token): array
    {
        return $this->client->get('/api/v1/internal/currencies', [], $token)['data']['currencies'] ?? [];
    }

    /** @return array<string,mixed> */
    public function setCountryActive(string $token, string $code, bool $active): array
    {
        return $this->client->put('/api/v1/internal/countries/'.$code.'/active', ['active' => $active], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function setCurrencyActive(string $token, string $code, bool $active): array
    {
        return $this->client->put('/api/v1/internal/currencies/'.$code.'/active', ['active' => $active], $token)['data'] ?? [];
    }
}
