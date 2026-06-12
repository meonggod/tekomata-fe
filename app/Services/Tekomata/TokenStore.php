<?php

namespace App\Services\Tekomata;

use Illuminate\Contracts\Session\Session;

/**
 * Server-side store for the Go API auth state. Tokens live in the Laravel
 * session ONLY — they are never sent to or read by the browser. Use a shared
 * session driver (database/redis) in any multi-instance deploy so the token
 * survives across app servers (see CLAUDE.md).
 */
class TokenStore
{
    private const ACCESS = 'tekomata.access_token';

    private const REFRESH = 'tekomata.refresh_token';

    private const EXPIRES_AT = 'tekomata.expires_at';

    private const USER = 'tekomata.user';

    private const COMPANY = 'tekomata.active_company';

    public function __construct(private readonly Session $session) {}

    /**
     * Persist a fresh access/refresh pair plus the identity it carries.
     *
     * @param  array<string,mixed>  $user
     * @param  array<string,mixed>|null  $activeCompany
     */
    public function put(string $accessToken, string $refreshToken, int $expiresInSeconds, array $user = [], ?array $activeCompany = null): void
    {
        $this->session->put(self::ACCESS, $accessToken);
        $this->session->put(self::REFRESH, $refreshToken);
        // Refresh 30s early to avoid using a token that expires mid-flight.
        $this->session->put(self::EXPIRES_AT, time() + max(0, $expiresInSeconds - 30));

        if ($user !== []) {
            $this->session->put(self::USER, $user);
        }

        if ($activeCompany !== null) {
            $this->session->put(self::COMPANY, $activeCompany);
        }
    }

    /**
     * Rotate just the tokens after a refresh, keeping the existing identity.
     */
    public function replaceTokens(string $accessToken, string $refreshToken, int $expiresInSeconds): void
    {
        $this->session->put(self::ACCESS, $accessToken);
        $this->session->put(self::REFRESH, $refreshToken);
        $this->session->put(self::EXPIRES_AT, time() + max(0, $expiresInSeconds - 30));
    }

    public function accessToken(): ?string
    {
        return $this->session->get(self::ACCESS);
    }

    public function refreshToken(): ?string
    {
        return $this->session->get(self::REFRESH);
    }

    public function check(): bool
    {
        return $this->accessToken() !== null;
    }

    public function isExpired(): bool
    {
        $expiresAt = $this->session->get(self::EXPIRES_AT);

        return $expiresAt !== null && time() >= (int) $expiresAt;
    }

    /**
     * The signed-in user's id, read from the access token's JWT `sub` claim.
     * The API exposes no /auth/me endpoint and login never stored the identity,
     * but the id is always carried in the token itself. Returns '' if absent.
     */
    public function userId(): string
    {
        $token = $this->accessToken();
        if ($token === null) {
            return '';
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return '';
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')) ?: '', true);

        return is_array($claims) ? (string) ($claims['sub'] ?? '') : '';
    }

    /**
     * @return array<string,mixed>|null
     */
    public function user(): ?array
    {
        return $this->session->get(self::USER);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function activeCompany(): ?array
    {
        return $this->session->get(self::COMPANY);
    }

    /**
     * Update just the active-company record without touching the token pair.
     * Used to enrich the stored company with a name once the companies list
     * has been fetched (e.g. on the first dashboard load after login).
     *
     * @param  array<string,mixed>  $company
     */
    public function putActiveCompany(array $company): void
    {
        $this->session->put(self::COMPANY, $company);
    }

    /**
     * Drop all auth state (logout / failed refresh).
     */
    public function forget(): void
    {
        $this->session->forget([
            self::ACCESS,
            self::REFRESH,
            self::EXPIRES_AT,
            self::USER,
            self::COMPANY,
        ]);
    }
}
