<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Email + password sign-in. Forwards credentials to the Go `/auth/login`; on
 * success the access/refresh pair is kept server-side in the session
 * (TokenStore) — never in JS-readable storage — and the user is sent to the
 * dashboard. Silent refresh on expiry is handled by EnsureAuthenticated.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly TokenStore $tokens,
    ) {}

    public function show(): View|RedirectResponse
    {
        // Already signed in? Skip the form.
        if ($this->tokens->check() && ! $this->tokens->isExpired()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:72'],
        ], [
            'email.required' => __('errors.validation.required'),
            'email.email' => __('errors.validation.email'),
            'email.max' => __('errors.validation.email'),
            'password.required' => __('errors.validation.required'),
            'password.max' => __('errors.validation.too_long', ['max' => 72]),
        ]);

        try {
            $data = $this->auth->login(
                strtolower(trim($validated['email'])),
                $validated['password'],
                $request->boolean('remember_me'),
            );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors() ?: ['email' => __('errors.validation_failed')])
                ->withInput($request->except('password'));
        } catch (ApiUnavailableException $e) {
            // 5xx / unreachable — not the user's input. Pop the "something went
            // wrong" modal over the form with the request id, leave the form intact.
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            // Generic for invalid_credentials (401) / email_not_verified (403) /
            // rate_limited — rendered from the catalog by code, never raw text.
            return back()
                ->withErrors(['email' => $e->localizedMessage()])
                ->withInput($request->except('password'));
        }

        $this->tokens->put(
            $data['access_token'] ?? '',
            $data['refresh_token'] ?? '',
            (int) ($data['expires_in'] ?? 0),
            activeCompany: isset($data['active_company_id'])
                ? ['company_id' => $data['active_company_id']]
                : null,
        );

        // New privilege level → rotate the session id (fixation defence).
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
