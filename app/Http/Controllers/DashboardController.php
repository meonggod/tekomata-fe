<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Illuminate\View\View;

/**
 * The authenticated landing page. Loads the user's company memberships so the
 * dashboard can offer a switcher to multi-company users. A failure to reach the
 * companies endpoint degrades quietly to a switcher-less dashboard.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly TokenStore $tokens,
    ) {}

    public function index(): View
    {
        $companies = [];
        $activeId = $this->tokens->activeCompany()['company_id'] ?? null;

        try {
            $data = $this->auth->companies((string) $this->tokens->accessToken());
            $companies = $data['companies'] ?? [];
            $activeId = $data['active_company_id'] ?? $activeId;
        } catch (TekomataApiException) {
            // Non-fatal: show the dashboard without the switcher.
        }

        return view('dashboard', [
            'companies' => $companies,
            'activeCompanyId' => $activeId,
        ]);
    }
}
