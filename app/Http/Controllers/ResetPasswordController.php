<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Step two of the password-reset flow: the set-a-new-password screen reached
 * from the email link (`/reset-password?token=...`). Carries the token from the
 * link, posts it with the new password to the Go `/auth/password/reset`, and on
 * success routes to login — the reset revokes the old session upstream, so the
 * user must sign in fresh. Unknown/used/expired tokens render a friendly error.
 */
class ResetPasswordController extends Controller
{
    public function __construct(private readonly AuthApi $auth) {}

    public function show(Request $request): View
    {
        // The token rides in the link's query string; carry it into the hidden
        // form field. A blank token still renders — the API rejects it on submit.
        return view('auth.reset-password', [
            'token' => (string) $request->query('token', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Mirror the API contract (token required; password 8–72) so obvious
        // mistakes never leave the browser. The Go API validates again (422).
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'token.required' => __('errors.auth.invalid_reset_token'),
            'password.required' => __('errors.validation.required'),
            'password.min' => __('errors.validation.too_short', ['min' => 8]),
            'password.max' => __('errors.validation.too_long', ['max' => 72]),
        ]);

        try {
            $this->auth->resetPassword($validated['token'], $validated['password']);
        } catch (ValidationException $e) {
            // Per-field codes from the API, localised + keyed to the form inputs.
            return back()
                ->withErrors($e->errors() ?: ['password' => __('errors.validation_failed')])
                ->withInput(['token' => $validated['token']]);
        } catch (ApiUnavailableException $e) {
            // 5xx / unreachable — not the user's input. Pop the "something went
            // wrong" modal over the form (token preserved), leave the form intact.
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            // Unknown / used / expired token (400 auth.invalid_reset_token) or
            // rate_limited — surfaced from the catalog by code, never raw text.
            return back()
                ->withErrors(['token' => $e->localizedMessage()])
                ->withInput(['token' => $validated['token']]);
        }

        // The old session is dead upstream — route to login to sign in fresh.
        return redirect()
            ->route('login')
            ->with('status', __('messages.reset.success'));
    }
}
