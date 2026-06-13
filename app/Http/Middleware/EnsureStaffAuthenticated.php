<?php

namespace App\Http\Middleware;

use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\StaffAuthApi;
use App\Services\Tekomata\StaffTokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the tekomata-staff console (`/internal/*`). The staff principal is a
 * STAFF JWT held server-side ({@see StaffTokenStore}) — a DISTINCT identity from
 * the tenant auth ({@see EnsureAuthenticated}). A normal company token is never
 * accepted here: it lives under different session keys and simply isn't present,
 * so a tenant user hitting `/internal/*` is bounced to the staff login.
 *
 * On an expired access token we transparently refresh once; on failure we clear
 * the staff session and redirect to the staff login.
 */
class EnsureStaffAuthenticated
{
    public function __construct(
        private readonly StaffTokenStore $tokens,
        private readonly StaffAuthApi $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tokens->check()) {
            return $this->unauthenticated($request);
        }

        if ($this->tokens->isExpired() && ! $this->refresh()) {
            $this->tokens->forget();

            return $this->unauthenticated($request);
        }

        return $next($request);
    }

    /** Attempt a single token refresh. Returns false on any failure. */
    private function refresh(): bool
    {
        $refreshToken = $this->tokens->refreshToken();

        if ($refreshToken === null) {
            return false;
        }

        try {
            $data = $this->auth->refresh($refreshToken);
        } catch (TekomataApiException) {
            return false;
        }

        $accessToken = $data['access_token'] ?? '';

        if ($accessToken === '') {
            return false;
        }

        $this->tokens->replaceTokens(
            $accessToken,
            $data['refresh_token'] ?? $refreshToken,
            (int) ($data['expires_in'] ?? 0),
        );

        return true;
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            abort(401, 'Not authenticated.');
        }

        return redirect()->guest(route('internal.login'));
    }
}
