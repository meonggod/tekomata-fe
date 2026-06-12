<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\SubscriptionApi;
use App\Services\Tekomata\TokenStore;
use App\Services\Tekomata\WalletApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Subscription plans page: shows the purchasable tiers, the company's current
 * plan + renewal/expiry, and subscribe / cancel actions. Thin — all plan + money
 * state lives in the Go subscription + wallet endpoints; this only renders it and
 * forwards the two actions. Subscribing draws from the spendable wallet, so an
 * insufficient balance nudges the owner to top up first.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionApi $subscription,
        private readonly WalletApi $wallet,
        private readonly TokenStore $tokens,
    ) {}

    public function show(): View
    {
        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');

        $plans = [];
        $current = [];
        try {
            $plans = $this->subscription->plans($token);
            $current = $this->subscription->current($token);
        } catch (TekomataApiException) {
            // Degrade gracefully — render with no plans / free-tier defaults.
        }

        // Surface the spendable balance so the owner can see, before subscribing,
        // whether a plan is affordable. A failed lookup just hides the figure.
        $spendable = null;
        try {
            $spendable = $this->wallet->get($token, $companyId, 1, 0)['spendable_balance'] ?? null;
        } catch (TekomataApiException) {
            // Leave the balance unknown — the backend stays the real affordability gate.
        }

        return view('subscription.index', [
            'plans' => $plans,
            'active' => (bool) ($current['active'] ?? false),
            'baseRate' => $current['base_rate'] ?? null,
            'subscription' => $current['subscription'] ?? null,
            'spendable' => $spendable,
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'max:64'],
        ], [
            'plan_id.required' => __('errors.validation.required'),
        ]);

        $token = $this->tokens->accessToken();

        try {
            $this->subscription->subscribe($token, $validated['plan_id']);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['subscription_action' => $e->localizedMessage()]);
        }

        return redirect()->route('subscription.index')
            ->with('status', __('messages.subscription.subscribed'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $this->subscription->cancel($token);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['subscription_action' => $e->localizedMessage()]);
        }

        return redirect()->route('subscription.index')
            ->with('status', __('messages.subscription.cancelled'));
    }
}
