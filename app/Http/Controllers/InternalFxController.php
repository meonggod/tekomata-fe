<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Admin\PlatformConfigApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Internal FX rates panel (tekomata-staff): current USD→IDR rates + freshness, a
 * manual "sync now", and the staleness max-age guard the charging engine reads
 * live. Re-exposed under the foundation's staff guard (`/api/v1/internal/fx/*`)
 * with the staff JWT — replacing the old X-Admin-Key path. The max-age write is
 * superadmin-only (route middleware); sync is open to any staff.
 */
class InternalFxController extends Controller
{
    public function __construct(
        private readonly PlatformConfigApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $rates = [];
        $settings = [];

        try {
            $rates = $this->api->fxRates($token);
            $settings = $this->api->platformSettings($token);
        } catch (TekomataApiException) {
            // Degrade gracefully — render the page with no rates.
        }

        return view('internal.fx', [
            'rates' => $rates,
            'maxAgeHours' => $settings['fx_max_age_hours'] ?? null,
            'isSuperadmin' => $this->tokens->isSuperadmin(),
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        try {
            $this->api->fxSync((string) $this->tokens->accessToken());
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['fx_sync' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.fx.synced'));
    }

    public function updateMaxAge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fx_max_age_hours' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->api->updatePlatformSetting((string) $this->tokens->accessToken(), 'fx_max_age_hours', $data['fx_max_age_hours']);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['fx_max_age_hours' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.fx.max_age_saved'));
    }
}
