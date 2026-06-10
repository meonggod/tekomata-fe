<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\TokenStore;
use Illuminate\View\View;

/**
 * Landing for the internal tekomata-staff area (/internal/dashboard).
 *
 * Thin scaffold for the ops/daily-job tooling — gated by auth.api +
 * internal.staff. Real screens (queues, tenant lookup, metering, etc.) get
 * added under this controller / the /internal route group as they are specced.
 */
class InternalDashboardController extends Controller
{
    public function __construct(private readonly TokenStore $tokens) {}

    public function index(): View
    {
        $user = $this->tokens->user() ?? [];

        return view('internal.dashboard', [
            'staffName' => $user['name'] ?? $user['email'] ?? 'staff',
        ]);
    }
}
