<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CatalogApi;
use App\Services\Tekomata\CompanyCurrencyApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Currency settings for the active company: browse the platform currency
 * catalog and choose the subset the company prices in, naming exactly one
 * default. The catalog is read from the public `GET /currencies`; enable /
 * disable / set-default post to the JWT-scoped company-currency endpoints, which
 * resolve the active company from the token. Thin — all HTTP lives in the
 * service layer; unexpected backend failures pop the shared error modal.
 */
class CurrencyController extends Controller
{
    public function __construct(
        private readonly CatalogApi $catalog,
        private readonly CompanyCurrencyApi $companyCurrencies,
        private readonly TokenStore $tokens,
    ) {}

    public function index(): View
    {
        $catalog = $this->catalog->currencies();
        $enabled = $this->companyCurrencies->enabled((string) $this->tokens->accessToken());

        // Index the company's set by code so the view can mark each catalog row
        // as enabled / default in one pass.
        $enabledByCode = [];

        foreach ($enabled as $row) {
            if (isset($row['code']) && is_string($row['code'])) {
                $enabledByCode[$row['code']] = $row;
            }
        }

        return view('currencies.index', [
            'catalog' => $catalog,
            'enabledByCode' => $enabledByCode,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
        ], [
            'currency_code.required' => __('errors.validation.required'),
            'currency_code.size' => __('errors.validation_failed'),
        ]);

        return $this->run(
            fn (string $token) => $this->companyCurrencies->enable($token, strtoupper($validated['currency_code'])),
            $request,
            'currencies.status_enabled',
        );
    }

    public function setDefault(Request $request, string $code): RedirectResponse
    {
        return $this->run(
            fn (string $token) => $this->companyCurrencies->setDefault($token, strtoupper($code)),
            $request,
            'currencies.status_default_set',
        );
    }

    public function disable(Request $request, string $code): RedirectResponse
    {
        return $this->run(
            function (string $token) use ($code) {
                $this->companyCurrencies->disable($token, strtoupper($code));

                return [];
            },
            $request,
            'currencies.status_disabled',
        );
    }

    /**
     * Shared shape for the three mutating actions: run the call, on success
     * flash a status and return to the settings page; on an unexpected backend
     * failure pop the error modal; on any other API error flash a localised,
     * code-derived message (never raw upstream text).
     *
     * @param  callable(string):mixed  $call
     */
    private function run(callable $call, Request $request, string $statusKey): RedirectResponse
    {
        try {
            $call((string) $this->tokens->accessToken());
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['currency' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.'.$statusKey));
    }
}
