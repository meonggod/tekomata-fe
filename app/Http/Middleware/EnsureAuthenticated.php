<?php

namespace App\Http\Middleware;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for pages that require a logged-in tekomata user.
 *
 * The identity is the Go API JWT held server-side (TokenStore). If the access
 * token has expired we transparently refresh it once; if that fails we clear
 * the session and bounce to login. Tenant scope (active company) rides inside
 * the token, so nothing tenant-related is trusted from the request.
 */
class EnsureAuthenticated
{
    public function __construct(
        private readonly TokenStore $tokens,
        private readonly AuthApi $auth,
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

    /**
     * Attempt a single token refresh. Returns false on any failure.
     */
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

        return redirect()->guest(route('login'));
    }
}
