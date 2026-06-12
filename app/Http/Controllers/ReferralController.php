<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\ReferralApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\View\View;

/**
 * Referral page: the company's shareable code/link, the companies it has
 * referred, and the reward earned. Thin — all state lives in the Go referral
 * endpoint; rewards land in the withdrawable reward wallet (managed on the
 * wallet page). The code is issued lazily by the API on first view.
 */
class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralApi $referral,
        private readonly TokenStore $tokens,
    ) {}

    public function show(): View
    {
        $token = $this->tokens->accessToken();

        $data = [];
        try {
            $data = $this->referral->overview($token);
        } catch (TekomataApiException) {
            // Degrade gracefully — render with no code/referrals.
        }

        return view('referral.index', [
            'code' => $data['code'] ?? null,
            'shareUrl' => $data['share_url'] ?? null,
            'totalReward' => $data['total_reward'] ?? '0',
            'referrals' => $data['referrals'] ?? [],
        ]);
    }
}
