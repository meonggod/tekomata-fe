<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\BillingApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use App\Services\Tekomata\WalletApi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Cost & billing panel: an aggregated, categorized view of what the company has
 * been charged for over a chosen period (usage / subscription / feature / AI),
 * alongside the wallet balance. Thin — the Go charging engine prices, converts,
 * debits and records; this only renders the breakdown it exposes and complements
 * the wallet page's raw ledger with a categorized one.
 */
class BillingController extends Controller
{
    /** Selectable look-back windows, in days. */
    private const PERIODS = [7, 30, 90];

    private const DEFAULT_PERIOD = 30;

    public function __construct(
        private readonly BillingApi $billing,
        private readonly WalletApi $wallet,
        private readonly TokenStore $tokens,
    ) {}

    public function show(Request $request): View
    {
        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');

        $period = (int) $request->query('period', self::DEFAULT_PERIOD);
        if (! in_array($period, self::PERIODS, true)) {
            $period = self::DEFAULT_PERIOD;
        }

        $to = Carbon::now();
        $from = $to->copy()->subDays($period);

        $breakdown = [];
        try {
            $breakdown = $this->billing->charges($token, $from->toDateString(), $to->toDateString());
        } catch (TekomataApiException) {
            // Degrade gracefully — render an empty breakdown.
        }

        // Show the spendable balance alongside the spend so the owner sees both
        // what they've spent and what's left. A failed lookup just hides it.
        $spendable = null;
        try {
            $spendable = $this->wallet->get($token, $companyId, 1, 0)['spendable_balance'] ?? null;
        } catch (TekomataApiException) {
            // Non-fatal.
        }

        return view('billing.index', [
            'period' => $period,
            'periods' => self::PERIODS,
            'from' => $breakdown['from'] ?? $from->toDateString(),
            'to' => $breakdown['to'] ?? $to->toDateString(),
            'totalIdr' => $breakdown['total_idr'] ?? '0',
            'byKind' => is_array($breakdown['by_kind'] ?? null) ? $breakdown['by_kind'] : [],
            'charges' => $breakdown['charges'] ?? [],
            'spendable' => $spendable,
        ]);
    }
}
