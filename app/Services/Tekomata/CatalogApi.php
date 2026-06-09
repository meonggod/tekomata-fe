<?php

namespace App\Services\Tekomata;

use Illuminate\Support\Facades\Cache;

/**
 * Read-only master catalogs of the Go API — countries and currencies. Both
 * endpoints are PUBLIC (registration needs the country list pre-login and
 * machine callers read them too), so no bearer token is attached. They return
 * only the platform-active rows; `is_active` filtering happens upstream.
 *
 * These lists are global and change rarely, but they sit on the critical render
 * path of public pages (the registration country dropdown). So the full,
 * unsearched list is cached briefly — without it every page load would block on
 * a fresh round-trip (and pay the full retry/timeout budget when the API is
 * slow or down). Searched calls bypass the cache; failures are never cached, so
 * the page just degrades and retries next time.
 */
class CatalogApi
{
    /** How long a fetched catalog list stays cached. Global + rarely-changing. */
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Active countries for the registration / profile dropdowns. Each row:
     * `country_code`, `name`, `default_currency_code`, `flag_image`, `dial_code`.
     *
     * @return array<int,array<string,mixed>>
     */
    public function countries(?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            return $this->client->get('/api/v1/countries', ['search' => $search])['data']['countries'] ?? [];
        }

        return Cache::remember('tekomata.catalog.countries', self::CACHE_TTL_SECONDS, function () {
            return $this->client->get('/api/v1/countries')['data']['countries'] ?? [];
        });
    }

    /**
     * Active currencies for product-pricing selection. Each row: `code`,
     * `name`, `symbol`, `symbol_native`, `decimal_places`, `display_hints`.
     *
     * @return array<int,array<string,mixed>>
     */
    public function currencies(?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            return $this->client->get('/api/v1/currencies', ['search' => $search])['data']['currencies'] ?? [];
        }

        return Cache::remember('tekomata.catalog.currencies', self::CACHE_TTL_SECONDS, function () {
            return $this->client->get('/api/v1/currencies')['data']['currencies'] ?? [];
        });
    }
}
