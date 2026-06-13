<?php

namespace App\Services\Tekomata;

/**
 * Staff-auth endpoints of the Go API — the tekomata-internal login surface, kept
 * deliberately separate from the tenant {@see AuthApi}. Staff log in with email +
 * password and receive their own access/refresh pair (no active company in it).
 * One thin method per call; session/token handling lives in {@see StaffTokenStore}.
 *
 * Paths live under `/api/v1/internal/auth/*`. Error codes: `staff.invalid_credentials`,
 * `staff.invalid_refresh_token`, `staff.invalid_token`, `staff.email_taken`,
 * `staff.forbidden`, `staff.not_found`.
 */
class StaffAuthApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Exchange email + password for a staff access/refresh pair.
     *
     * @return array<string,mixed>
     */
    public function login(string $email, string $password): array
    {
        return $this->client->post('/api/v1/internal/auth/login', [
            'email' => $email,
            'password' => $password,
        ])['data'] ?? [];
    }

    /**
     * Mint a new pair from a valid refresh token (rotated, single-use upstream).
     *
     * @return array<string,mixed>
     */
    public function refresh(string $refreshToken): array
    {
        return $this->client->post('/api/v1/internal/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])['data'] ?? [];
    }

    /** Best-effort server-side revocation of a refresh token on logout. */
    public function logout(string $accessToken, string $refreshToken): void
    {
        $this->client->post('/api/v1/internal/auth/logout', ['refresh_token' => $refreshToken], $accessToken);
    }

    /**
     * Start a staff password reset. Always responds generically (no account
     * enumeration); a single-use, expiring link is emailed if the account exists.
     *
     * @return array<string,mixed>
     */
    public function forgotPassword(string $email): array
    {
        return $this->client->post('/api/v1/internal/auth/password/forgot', [
            'email' => $email,
        ]);
    }

    /**
     * Set a password from an invite / reset token (single-use, time-boxed). Used
     * for both the first-login invite flow and a forgotten-password reset. Does
     * NOT issue a JWT — login is a separate step.
     *
     * @return array<string,mixed>
     */
    public function setPassword(string $token, string $password): array
    {
        return $this->client->post('/api/v1/internal/auth/set-password', [
            'token' => $token,
            'password' => $password,
        ]);
    }
}
