<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing page for the click-through from the verification email
 * (`/verify?token=...`). Confirms the token against the Go `/auth/verify`; on
 * success the user's account + first company exist, so we route them to login
 * (verify does not auto-login) and on into the dashboard. A missing, expired or
 * already-used token renders a friendly "link no longer valid" page.
 */
class VerifyController extends Controller
{
    public function __construct(private readonly AuthApi $auth) {}

    public function verify(Request $request): View|RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return view('auth.verify', ['failed' => true]);
        }

        try {
            $this->auth->verify($token);
        } catch (TekomataApiException) {
            // Unknown / used / expired (400) or missing (422) — all generic,
            // no detail leaked. ApiUnavailableException bubbles to the 503 page.
            return view('auth.verify', ['failed' => true]);
        }

        return redirect()
            ->route('login')
            ->with('status', __('messages.register.verified'));
    }
}
