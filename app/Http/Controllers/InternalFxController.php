<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\AdminFxApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Internal FX rates view (tekomata-staff only): current USD→IDR rates, their
 * freshness, and a "sync now" action. Talks to the platform-admin FX endpoints
 * via the X-Admin-Key'd AdminFxApi — not the tenant JWT. Thin: the backend keeps
 * rates fresh on a schedule; this just surfaces them and forwards a manual sync.
 */
class InternalFxController extends Controller
{
    public function __construct(private readonly AdminFxApi $fx) {}

    public function index(): View
    {
        $configured = $this->fx->configured();
        $rates = [];

        if ($configured) {
            try {
                $rates = $this->fx->rates();
            } catch (TekomataApiException) {
                // Degrade gracefully — render the page with no rates.
            }
        }

        return view('internal.fx', [
            'configured' => $configured,
            'rates' => $rates,
        ]);
    }

    public function sync(): RedirectResponse
    {
        try {
            $this->fx->sync();
        } catch (TekomataApiException $e) {
            // e.g. fx.sync_unavailable (provider not configured) — surfaced from
            // the catalog by code, never raw upstream text. Last good rate stays.
            return back()->withErrors(['fx_sync' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.fx.synced'));
    }
}
