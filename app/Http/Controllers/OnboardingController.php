<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureOnboarded;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\OnboardingApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Guides a newly-verified user through the one-time KYC + KYB onboarding flow.
 *
 * Steps: KYC (personal identity) → KYB (business profile). Both are upserts on
 * the Go API side, so re-visiting a completed step is safe. When KYB succeeds the
 * gate is lifted (gated=false written into the session) and the user lands on the
 * dashboard.
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingApi $onboarding,
        private readonly TokenStore $tokens,
    ) {}

    /**
     * Route the user to whichever step is still incomplete.
     */
    public function show(): RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $req = $this->onboarding->requirements($token);
        } catch (TekomataApiException) {
            return redirect()->route('onboarding.kyc');
        }

        if (! ($req['gated'] ?? true)) {
            return redirect()->route('dashboard');
        }

        $kyc = $req['kyc'] ?? [];

        if (($kyc['status'] ?? 'incomplete') !== 'complete') {
            return redirect()->route('onboarding.kyc');
        }

        return redirect()->route('onboarding.kyb');
    }

    public function showKyc(): View|RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $req = $this->onboarding->requirements($token);
        } catch (TekomataApiException) {
            return view('onboarding.kyc', ['missing' => []]);
        }

        if (! ($req['gated'] ?? true)) {
            return redirect()->route('dashboard');
        }

        $kyc = $req['kyc'] ?? [];

        if (($kyc['status'] ?? 'incomplete') === 'complete') {
            return redirect()->route('onboarding.kyb');
        }

        return view('onboarding.kyc', [
            'missing' => $kyc['missing'] ?? [],
        ]);
    }

    public function storeKyc(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'id_type' => ['required', 'string', 'in:ktp,passport,sim'],
            'id_number' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
        ], [
            'full_name.required' => __('errors.validation.required'),
            'full_name.max' => __('errors.validation.too_long', ['max' => 255]),
            'date_of_birth.required' => __('errors.validation.required'),
            'date_of_birth.date' => __('errors.validation.invalid_value'),
            'id_type.required' => __('errors.validation.required'),
            'id_type.in' => __('errors.validation.invalid_value'),
            'id_number.required' => __('errors.validation.required'),
            'id_number.max' => __('errors.validation.too_long', ['max' => 50]),
            'address.required' => __('errors.validation.required'),
            'address.max' => __('errors.validation.too_long', ['max' => 500]),
        ]);

        $token = $this->tokens->accessToken();

        $payload = $validated;
        $payload['date_of_birth'] = (int) (strtotime($validated['date_of_birth']) * 1000);

        try {
            $this->onboarding->submitKyc($token, $payload);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors() ?: ['full_name' => __('errors.validation_failed')])
                ->withInput($request->all());
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()
                ->withErrors(['full_name' => $e->localizedMessage()])
                ->withInput($request->all());
        }

        return redirect()->route('onboarding.kyb');
    }

    public function showKyb(): View|RedirectResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $req = $this->onboarding->requirements($token);
        } catch (TekomataApiException) {
            return view('onboarding.kyb', ['missing' => []]);
        }

        if (! ($req['gated'] ?? true)) {
            return redirect()->route('dashboard');
        }

        $kyc = $req['kyc'] ?? [];

        if (($kyc['status'] ?? 'incomplete') !== 'complete') {
            return redirect()->route('onboarding.kyc');
        }

        return view('onboarding.kyb', [
            'missing' => ($req['kyb'] ?? [])['missing'] ?? [],
        ]);
    }

    public function storeKyb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:pt,cv,ud,perorangan,other'],
            'registration_number' => ['required', 'string', 'max:100'],
            'tax_id' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
        ], [
            'legal_name.required' => __('errors.validation.required'),
            'legal_name.max' => __('errors.validation.too_long', ['max' => 255]),
            'business_type.required' => __('errors.validation.required'),
            'business_type.in' => __('errors.validation.invalid_value'),
            'registration_number.required' => __('errors.validation.required'),
            'registration_number.max' => __('errors.validation.too_long', ['max' => 100]),
            'tax_id.required' => __('errors.validation.required'),
            'tax_id.max' => __('errors.validation.too_long', ['max' => 50]),
            'address.required' => __('errors.validation.required'),
            'address.max' => __('errors.validation.too_long', ['max' => 500]),
        ]);

        $token = $this->tokens->accessToken();

        try {
            $this->onboarding->submitKyb($token, $validated);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors() ?: ['legal_name' => __('errors.validation_failed')])
                ->withInput($request->all());
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()
                ->withErrors(['legal_name' => $e->localizedMessage()])
                ->withInput($request->all());
        }

        // Lift the gate — both KYC and KYB are now complete.
        $request->session()->put(EnsureOnboarded::GATE_KEY, false);

        return redirect()->route('dashboard')->with('status', __('messages.onboarding.completed'));
    }
}
