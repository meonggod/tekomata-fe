<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
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
    public function __construct(private readonly AuthApi $auth) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Mirror the API contract (valid email; password 8–72) so obvious
        // mistakes never leave the browser. The Go API validates again (422).
        // Messages reuse the shared error catalog keyed by the API's field codes.
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'email.required' => __('errors.validation.required'),
            'email.email' => __('errors.validation.email'),
            'email.max' => __('errors.validation.email'),
            'password.required' => __('errors.validation.required'),
            'password.min' => __('errors.validation.too_short', ['min' => 8]),
            'password.max' => __('errors.validation.too_long', ['max' => 72]),
        ]);

        $email = strtolower(trim($validated['email']));

        try {
            $this->auth->register($email, $validated['password']);
        } catch (ValidationException $e) {
            // Per-field codes from the API, localised + keyed to the form inputs.
            return back()
                ->withErrors($e->errors() ?: ['email' => __('errors.validation_failed')])
                ->withInput($request->except('password'));
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
