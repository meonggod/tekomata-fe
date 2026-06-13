<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Admin\PlatformConfigApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Billing & platform config panel — subscription plans, the feature-pricing
 * catalog, promo codes, and the platform settings bag (wallet payout min/fee,
 * referral reward cap, FX staleness max-age) on one page. Reads are open to any
 * staff; the mutating actions are superadmin-only (enforced by the
 * `internal.superadmin` route middleware + hidden in the view for `ops`).
 *
 * The page degrades gracefully: any section whose endpoint is unavailable simply
 * renders empty rather than failing the whole panel.
 */
class BillingConfigController extends Controller
{
    public function __construct(
        private readonly PlatformConfigApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();

        return view('internal.billing-config', [
            'isSuperadmin' => $this->tokens->isSuperadmin(),
            'plans' => $this->safe(fn () => $this->api->plans($token)),
            'features' => $this->safe(fn () => $this->api->featurePrices($token)),
            'promoCodes' => $this->safe(fn () => $this->api->promoCodes($token)),
            'settings' => $this->safe(fn () => $this->api->platformSettings($token), []),
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'referral_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $this->mutate($request, fn (string $t) => $this->api->createPlan($t, $data), 'plan_created');
    }

    public function updatePlan(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'referral_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $this->mutate($request, fn (string $t) => $this->api->updatePlan($t, $id, $data), 'plan_updated');
    }

    public function updateFeaturePrice(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'min:0']]);

        return $this->mutate($request, fn (string $t) => $this->api->updateFeaturePrice($t, $key, $data), 'feature_updated');
    }

    public function storePromoCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'usage_cap' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $this->mutate($request, fn (string $t) => $this->api->createPromoCode($t, $data), 'promo_created');
    }

    public function updatePromoCode(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'usage_cap' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $this->mutate($request, fn (string $t) => $this->api->updatePromoCode($t, $id, $data), 'promo_updated');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fx_max_age_hours' => ['nullable', 'numeric', 'min:0'],
            'wallet_payout_min' => ['nullable', 'numeric', 'min:0'],
            'wallet_payout_fee' => ['nullable', 'numeric', 'min:0'],
            'referral_reward_cap' => ['nullable', 'numeric', 'min:0'],
        ]);

        $token = (string) $this->tokens->accessToken();

        try {
            // Each key is typed-validated upstream and written individually so a
            // single bad value can't drop the others.
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $this->api->updatePlatformSetting($token, $key, $value);
                }
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['settings' => $e->localizedMessage()]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['settings' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.billing.flash.settings_updated'));
    }

    /**
     * Run a mutating call, mapping the typed exceptions to the panel's error /
     * flash conventions. `$flashKey` is a key under `internal.billing.flash.*`.
     */
    private function mutate(Request $request, callable $call, string $flashKey): RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $call($token);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['form' => $e->localizedMessage()]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['form' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.billing.flash.'.$flashKey));
    }

    /**
     * Read a list/section, degrading to a fallback when its endpoint is missing
     * or failing so one dead section never takes the whole panel down.
     *
     * @template T
     *
     * @param  callable():T  $call
     * @param  T  $fallback
     * @return T
     */
    private function safe(callable $call, mixed $fallback = [])
    {
        try {
            return $call();
        } catch (TekomataApiException) {
            return $fallback;
        }
    }
}
