<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\StaffTokenStore;
use Illuminate\View\View;

/**
 * Landing for the tekomata-staff console (/internal/dashboard). Gated by the
 * staff guard (internal.auth) — the signed-in principal is a staff member, not a
 * tenant user. Surfaces the staff identity + role and links into the config
 * panels (which carry their own controllers under the /internal route group).
 */
class InternalDashboardController extends Controller
{
    public function __construct(private readonly StaffTokenStore $tokens) {}

    public function index(): View
    {
        return view('internal.dashboard', [
            'staffEmail' => $this->tokens->email() ?: 'staff',
            'role' => $this->tokens->role(),
            'isSuperadmin' => $this->tokens->isSuperadmin(),
        ]);
    }
}
