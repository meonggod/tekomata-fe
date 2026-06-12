<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\CatalogApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minimal email + password signup. Renders the form and forwards credentials to
 * the Go `/auth/register`. The API never reveals whether an email already
 * exists, so a successful submit always lands on the same generic "check your
 * email" state — no real account is created until the emailed link is verified.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly CatalogApi $catalog,
    ) {}

    public function show(Request $request): View
    {
        // The country dropdown is an optional convenience — if the public
        // catalog is unreachable, signup must still work, so degrade to an
        // empty list (the field simply isn't offered) rather than a dead page.
        $countries = [];

        try {
            $countries = $this->catalog->countries();
        } catch (TekomataApiException) {
            // Non-fatal: render the form without the country selector.
        }

        return view('auth.register', [
            'countries' => $countries,
            // Prefillable from a campaign link (e.g. /register?promo_code=WELCOME50).
            'promoCode' => (string) $request->query('promo_code', ''),
            // Prefillable from a share link — accept the short `?ref=` form the
            // referral share URL uses, falling back to the explicit field name.
            'referralCode' => (string) ($request->query('ref') ?? $request->query('referral_code', '')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Mirror the API contract (valid email; password 8–72) so obvious
        // mistakes never leave the browser. The Go API validates again (422).
        // Messages reuse the shared error catalog keyed by the API's field codes.
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
            // Optional, backward-compatible. ISO 3166-1 alpha-2; the Go API is
            // the authority on which codes are active (422 invalid_country).
            'country_code' => ['nullable', 'string', 'size:2'],
            // Optional welcome-credit code. The Go API is the authority on
            // validity/expiry/cap (422 fields[promo_code]); we only forward it.
            'promo_code' => ['nullable', 'string', 'max:64'],
            // Optional referral code. The Go API validates it (422
            // fields[referral_code]) and binds attribution at verify time.
            'referral_code' => ['nullable', 'string', 'max:64'],
        ], [
            'email.required' => __('errors.validation.required'),
            'email.email' => __('errors.validation.email'),
            'email.max' => __('errors.validation.email'),
            'password.required' => __('errors.validation.required'),
            'password.min' => __('errors.validation.too_short', ['min' => 8]),
            'password.max' => __('errors.validation.too_long', ['max' => 72]),
        ]);

        $email = strtolower(trim($validated['email']));
        $countryCode = isset($validated['country_code']) ? strtoupper($validated['country_code']) : null;
        $promoCode = isset($validated['promo_code']) ? trim($validated['promo_code']) : null;
        $referralCode = isset($validated['referral_code']) ? trim($validated['referral_code']) : null;

        try {
            $this->auth->register($email, $validated['password'], $countryCode, $promoCode, $referralCode);
        } catch (ValidationException $e) {
            // Per-field codes from the API, localised + keyed to the form inputs.
            return back()
                ->withErrors($e->errors() ?: ['email' => __('errors.validation_failed')])
                ->withInput($request->except('password'));
        } catch (ApiUnavailableException $e) {
            // 5xx / unreachable — not the user's input. Pop the "something went
            // wrong" modal over the form with the request id, leave the form intact.
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            // e.g. rate_limited (per-IP limit / per-email resend cooldown).
            // Rendered from the error catalog by code, never raw upstream text.
            return back()
                ->withErrors(['email' => $e->localizedMessage()])
                ->withInput($request->except('password'));
        }

        // Generic success — no email enumeration. Show the "check your email"
        // state, remembering the address only to display it back to the user.
        return redirect()
            ->route('register')
            ->with('registered', true)
            ->with('email', $email);
    }
}
