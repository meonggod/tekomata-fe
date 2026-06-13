<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Admin\PlatformConfigApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Countries & currencies panel — the platform-level active flags that decide
 * which a company can pick at registration / in settings. Reads open to any
 * staff; the active toggles are superadmin-only (route middleware).
 */
class RegionController extends Controller
{
    public function __construct(
        private readonly PlatformConfigApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $countries = [];
        $currencies = [];

        try {
            $countries = $this->api->countries($token);
            $currencies = $this->api->currencies($token);
        } catch (TekomataApiException) {
            // Degrade gracefully.
        }

        return view('internal.regions', [
            'countries' => $countries,
            'currencies' => $currencies,
            'isSuperadmin' => $this->tokens->isSuperadmin(),
        ]);
    }

    public function toggleCountry(Request $request, string $code): RedirectResponse
    {
        return $this->toggle($request, fn (string $t, bool $a) => $this->api->setCountryActive($t, $code, $a));
    }

    public function toggleCurrency(Request $request, string $code): RedirectResponse
    {
        return $this->toggle($request, fn (string $t, bool $a) => $this->api->setCurrencyActive($t, $code, $a));
    }

    private function toggle(Request $request, callable $call): RedirectResponse
    {
        $active = $request->boolean('active');

        try {
            $call((string) $this->tokens->accessToken(), $active);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['region' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.regions.flash.updated'));
    }
}
