<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\UnauthorizedException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\StaffAuthApi;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * tekomata-staff authentication for the `/internal/*` console. Wholly separate
 * from the tenant LoginController: a different audience, a different identity
 * (StaffTokenStore, not TokenStore), and its own login/forgot/set-password
 * screens. Mirrors the tenant auth flow (email + password, email-link reset) but
 * never touches the tenant session.
 */
class StaffAuthController extends Controller
{
    public function __construct(
        private readonly StaffAuthApi $auth,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if ($this->tokens->check()) {
            return redirect()->route('internal.dashboard');
        }

        return view('internal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $tokens = $this->auth->login($data['email'], $data['password']);
        } catch (UnauthorizedException) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('messages.internal.auth.invalid_credentials')]);
        } catch (ValidationException $e) {
            return back()->withInput($request->only('email'))->withErrors($e->errors() ?: [
                'email' => $e->localizedMessage(),
            ]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        }

        $access = $tokens['access_token'] ?? '';
        if ($access === '') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('messages.internal.auth.invalid_credentials')]);
        }

        $request->session()->regenerate();
        $this->tokens->put(
            $access,
            $tokens['refresh_token'] ?? '',
            (int) ($tokens['expires_in'] ?? 0),
        );

        return redirect()->intended(route('internal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $access = $this->tokens->accessToken();
        $refresh = $this->tokens->refreshToken();

        if ($access !== null && $refresh !== null) {
            try {
                $this->auth->logout($access, $refresh);
            } catch (TekomataApiException) {
                // ignore — local sign-out still proceeds
            }
        }

        $this->tokens->forget();
        $request->session()->regenerateToken();

        return redirect()->route('internal.login');
    }

    public function showForgotPassword(): View
    {
        return view('internal.auth.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            $this->auth->forgotPassword($data['email']);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException) {
            // Generic response regardless — no account enumeration.
        }

        return back()->with('status', __('messages.internal.auth.forgot_sent'));
    }

    public function showSetPassword(Request $request): View
    {
        return view('internal.auth.set-password', [
            'token' => (string) $request->query('token', ''),
        ]);
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->auth->setPassword($data['token'], $data['password']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors() ?: ['token' => $e->localizedMessage()]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['token' => $e->localizedMessage()]);
        }

        return redirect()->route('internal.login')->with('status', __('messages.internal.auth.password_set'));
    }
}
