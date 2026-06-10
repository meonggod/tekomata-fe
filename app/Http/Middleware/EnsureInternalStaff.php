<?php

namespace App\Http\Middleware;

use App\Services\Tekomata\TokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the internal tekomata-staff area (/internal/*).
 *
 * Runs AFTER `auth.api`, so the caller is already a logged-in tekomata user;
 * this middleware only decides whether that user is *staff*. Authorization is
 * deny-by-default: a normal tenant user who stumbles onto /internal gets a 403,
 * never a peek at the ops tooling.
 *
 * A user counts as staff when EITHER the identity the Go API returned marks them
 * as such (`is_staff`/`is_admin` true, or a role of staff/admin/internal) OR
 * their email is on the configured allowlist (`services.tekomata.internal_emails`,
 * from TEKOMATA_INTERNAL_EMAILS). The allowlist is the stop-gap until the API
 * exposes a first-class staff claim; with no claim and an empty list, nobody is
 * staff — which is the safe default.
 */
class EnsureInternalStaff
{
    public function __construct(private readonly TokenStore $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isStaff()) {
            abort(403, 'This area is restricted to tekomata staff.');
        }

        return $next($request);
    }

    private function isStaff(): bool
    {
        $user = $this->tokens->user() ?? [];

        // First-class staff claim from the API, if present.
        if (($user['is_staff'] ?? false) === true || ($user['is_admin'] ?? false) === true) {
            return true;
        }

        $role = strtolower((string) ($user['role'] ?? ''));
        if (in_array($role, ['staff', 'admin', 'internal'], true)) {
            return true;
        }

        // Fallback: configured email allowlist (deny-by-default when empty).
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email === '') {
            return false;
        }

        $allow = array_map('strtolower', (array) config('services.tekomata.internal_emails', []));

        return in_array($email, $allow, true);
    }
}
