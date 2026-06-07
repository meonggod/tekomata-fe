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
 * Step one of the password-reset flow: collect an email and ask the Go
 * `/auth/password/forgot` to send a reset link. The API never reveals whether
 * the email exists, so an accepted submit always lands on the same generic
 * "if that email exists, we sent a link" state — no account enumeration.
 */
class ForgotPasswordController extends Controller
{
    public function __construct(private readonly AuthApi $auth) {}

    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        // Mirror the API contract so obvious mistakes never leave the browser;
        // the Go API validates again (422). Messages reuse the shared catalog.
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
        ], [
            'email.required' => __('errors.validation.required'),
            'email.email' => __('errors.validation.email'),
            'email.max' => __('errors.validation.email'),
        ]);

        $email = strtolower(trim($validated['email']));

        try {
            $this->auth->forgotPassword($email);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors() ?: ['email' => __('errors.validation_failed')])
                ->withInput();
        } catch (ApiUnavailableException $e) {
            // 5xx / unreachable — not the user's input. Pop the "something went
            // wrong" modal over the form with the request id, leave the form intact.
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            // e.g. rate_limited (per-IP limit). Rendered from the catalog by
            // code, never raw upstream text. ApiUnavailable bubbles to the 503.
            return back()
                ->withErrors(['email' => $e->localizedMessage()])
                ->withInput();
        }

        // Generic success — identical for known and unknown emails. Show the
        // "check your email" state, remembering the address only to echo it back.
        return redirect()
            ->route('password.request')
            ->with('sent', true)
            ->with('email', $email);
    }
}
