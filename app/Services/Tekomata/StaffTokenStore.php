<?php

namespace App\Services\Tekomata;

use Illuminate\Contracts\Session\Session;

/**
 * Server-side store for the tekomata-STAFF auth state — wholly separate from the
 * tenant {@see TokenStore}. Staff are platform operators, not company users: they
 * have no active company, a distinct JWT, and their own session keys so a staff
 * session can never be mistaken for (or collide with) a tenant one.
 *
 * Like the tenant store the tokens live in the Laravel session ONLY and are never
 * exposed to the browser. The signed-in staff id + role + email are decoded from
 * the access-token JWT (`sub` / `role` / `email` claims) — there is no separately
 * stored identity to keep in sync.
 */
class StaffTokenStore
{
    private const ACCESS = 'tekomata.staff.access_token';

    private const REFRESH = 'tekomata.staff.refresh_token';

    private const EXPIRES_AT = 'tekomata.staff.expires_at';

    public function __construct(private readonly Session $session) {}

    public function put(string $accessToken, string $refreshToken, int $expiresInSeconds): void
    {
        $this->session->put(self::ACCESS, $accessToken);
        $this->session->put(self::REFRESH, $refreshToken);
        // Refresh 30s early to avoid using a token that expires mid-flight.
        $this->session->put(self::EXPIRES_AT, time() + max(0, $expiresInSeconds - 30));
    }

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

    /** The signed-in staff id, from the access token's `sub` claim. */
    public function staffId(): string
    {
        return (string) ($this->claims()['sub'] ?? '');
    }

    /** The staff email, from the access token's `email` claim. */
    public function email(): string
    {
        return (string) ($this->claims()['email'] ?? '');
    }

    /**
     * The staff role (`superadmin` | `ops`), from the access token's `role` claim.
     * Defaults to the most-restricted `ops` when absent so an unknown token can
     * never gain mutate rights.
     */
    public function role(): string
    {
        $role = strtolower((string) ($this->claims()['role'] ?? 'ops'));

        return $role !== '' ? $role : 'ops';
    }

    /** Only `superadmin` may make money-moving config writes. */
    public function isSuperadmin(): bool
    {
        return $this->role() === 'superadmin';
    }

    public function forget(): void
    {
        $this->session->forget([self::ACCESS, self::REFRESH, self::EXPIRES_AT]);
    }

    /**
     * Decode the access token's JWT payload (claims). Returns [] when there is no
     * token or it isn't a well-formed JWT — callers fall back to safe defaults.
     *
     * @return array<string,mixed>
     */
    private function claims(): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return [];
        }

        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return [];
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')) ?: '', true);

        return is_array($claims) ? $claims : [];
    }
}
