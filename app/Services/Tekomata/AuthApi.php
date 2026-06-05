<?php

namespace App\Services\Tekomata;

/**
 * Auth endpoints of the Go API. One thin method per call — no logic beyond
 * shaping the request/response. Session/token handling lives in TokenStore;
 * orchestration lives in the controllers/middleware.
 *
 * NOTE: paths below mirror company.md's auth model. Confirm the exact routes
 * against the Go API repo when the auth story is implemented.
 */
class AuthApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Start a minimal email + password signup. The API stores a pending
     * registration and sends a verification email; it never reveals whether the
     * email already exists, so the response is always generic (HTTP 202). No
     * real account is created until the link is verified.
     *
     * @return array<string,mixed>
     */
    public function register(string $email, string $password): array
    {
        return $this->client->post('/api/v1/auth/register', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Confirm a verification token from the email link. On success the API
     * promotes the pending registration into a real user + first company and
     * returns their ids. Does NOT issue a JWT — login is a separate step.
     *
     * @return array<string,mixed>
     */
    public function verify(string $token): array
    {
        return $this->client->post('/api/v1/auth/verify', [
            'token' => $token,
        ]);
    }

    /**
     * Exchange email + password for an access/refresh pair (scoped to the
     * user's active company).
     *
     * @return array<string,mixed>
     */
    public function login(string $email, string $password, bool $rememberMe = false): array
    {
        return $this->client->post('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'remember_me' => $rememberMe,
        ])['data'] ?? [];
    }

    /**
     * Mint a new access/refresh pair from a valid refresh token (rotated,
     * single-use upstream).
     *
     * @return array<string,mixed> The token pair (unwrapped from the envelope).
     */
    public function refresh(string $refreshToken): array
    {
        return $this->client->post('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])['data'] ?? [];
    }

    /**
     * The identity behind an access token (id + active company).
     *
     * @return array<string,mixed>
     */
    public function me(string $accessToken): array
    {
        return $this->client->get('/api/v1/auth/me', token: $accessToken)['data'] ?? [];
    }

    /**
     * Best-effort server-side revocation of a refresh token on logout.
     */
    public function logout(string $accessToken, string $refreshToken): void
    {
        $this->client->post('/api/v1/auth/logout', ['refresh_token' => $refreshToken], $accessToken);
    }

    /**
     * The caller's company memberships + which one is active. Drives the
     * dashboard company switcher.
     *
     * @return array{active_company_id?:string,companies?:array<int,array<string,mixed>>}
     */
    public function companies(string $accessToken): array
    {
        return $this->client->get('/api/v1/auth/companies', token: $accessToken)['data'] ?? [];
    }

    /**
     * Switch the active company for a multi-company user; the API re-issues
     * tokens carrying the new active company.
     *
     * @return array<string,mixed> The new token pair (unwrapped from the envelope).
     */
    public function switchCompany(string $accessToken, string $companyId): array
    {
        return $this->client->post('/api/v1/auth/switch-company', ['company_id' => $companyId], $accessToken)['data'] ?? [];
    }
}
