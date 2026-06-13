<?php

namespace App\Http\Middleware;

use App\Services\Tekomata\StaffTokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts money-moving config WRITES to `superadmin` staff. Runs after
 * {@see EnsureStaffAuthenticated}, so the caller is already a logged-in staff
 * member; this only checks their role. Reads stay open to any staff (incl.
 * view-only `ops`); the panels also hide mutate controls for non-superadmins,
 * but this is the server-side enforcement the backend mirrors with
 * `RequireStaffMutate`.
 */
class EnsureStaffSuperadmin
{
    public function __construct(private readonly StaffTokenStore $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tokens->isSuperadmin()) {
            abort(403, 'This action is restricted to tekomata superadmins.');
        }

        return $next($request);
    }
}
