<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AuthApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switches the active company for a multi-company user. The Go API re-issues a
 * token pair scoped to the chosen company; we re-store it so every later
 * request rides the new active-company scope carried in the JWT.
 */
class CompanyController extends Controller
{
    public function __construct(
        private readonly AuthApi $auth,
        private readonly TokenStore $tokens,
    ) {}

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'string'],
        ]);

        try {
            $data = $this->auth->switchCompany(
                (string) $this->tokens->accessToken(),
                $validated['company_id'],
            );
        } catch (ApiUnavailableException $e) {
            // 5xx / unreachable — pop the "something went wrong" modal over the
            // dashboard with the request id; the active company is unchanged.
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['company_id' => $e->localizedMessage()]);
        }

        $this->tokens->put(
            $data['access_token'] ?? '',
            $data['refresh_token'] ?? '',
            (int) ($data['expires_in'] ?? 0),
            activeCompany: ['company_id' => $data['active_company_id'] ?? $validated['company_id']],
        );

        return redirect()->route('dashboard');
    }
}
