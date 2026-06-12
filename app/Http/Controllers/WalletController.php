<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CompanySettingsApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\TokenStore;
use App\Services\Tekomata\WalletApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Prepaid IDR wallet page: two balances (spendable + reward), top-up, convert,
 * withdraw, and the bucket-tagged transaction history. Thin — all money state
 * lives in the Go wallet endpoints; this only renders it and forwards actions.
 */
class WalletController extends Controller
{
    /** Page size for the transaction ledger. */
    private const PAGE_SIZE = 20;

    /** Warn the owner when spendable drops below this many IDR. */
    private const LOW_BALANCE_THRESHOLD = 50000;

    public function __construct(
        private readonly WalletApi $wallet,
        private readonly CompanySettingsApi $settings,
        private readonly TokenStore $tokens,
    ) {}

    public function show(Request $request): View
    {
        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');
        $offset = max(0, (int) $request->query('offset', 0));

        $wallet = [];
        try {
            $wallet = $this->wallet->get($token, $companyId, self::PAGE_SIZE, $offset);
        } catch (TekomataApiException) {
            // Degrade gracefully — render with empty balances/history.
        }

        // Withdraw is gated on a verified KYB profile + bank account. The backend
        // is the real gate (returns wallet.withdraw_not_allowed); we just surface
        // the state so the form isn't a dead end. A failed lookup hides withdraw.
        $kybVerified = false;
        try {
            $kyb = $this->settings->getSettings($token)['kyb'] ?? null;
            $kybVerified = is_array($kyb) && ($kyb['status'] ?? null) === 'verified';
        } catch (TekomataApiException) {
            // Leave withdraw gated closed on a lookup failure.
        }

        $transactions = $wallet['transactions'] ?? [];

        return view('wallet.index', [
            'spendable' => $wallet['spendable_balance'] ?? '0',
            'reward' => $wallet['reward_balance'] ?? '0',
            'transactions' => $transactions,
            'offset' => $offset,
            'pageSize' => self::PAGE_SIZE,
            'hasMore' => count($transactions) >= self::PAGE_SIZE,
            'lowThreshold' => self::LOW_BALANCE_THRESHOLD,
            'kybVerified' => $kybVerified,
        ]);
    }

    public function topup(Request $request): RedirectResponse
    {
        $validated = $this->validateAmount($request, 'topup');

        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');

        try {
            $result = $this->wallet->topup($token, $companyId, $validated['amount']);
        } catch (ValidationException $e) {
            return $this->fieldError($e, 'topup');
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return $this->actionError($e, 'topup');
        }

        // The provider checkout is the source of truth for the credit — send the
        // owner there. The webhook credits the spendable balance on confirmation.
        $paymentUrl = $result['payment_url'] ?? null;
        if (is_string($paymentUrl) && $paymentUrl !== '') {
            return redirect()->away($paymentUrl);
        }

        return redirect()->route('wallet.index')
            ->with('status', __('messages.wallet.topup.started'))
            ->with('status_section', 'topup');
    }

    public function convert(Request $request): RedirectResponse
    {
        $validated = $this->validateAmount($request, 'convert');

        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');

        try {
            $this->wallet->convert($token, $companyId, $validated['amount']);
        } catch (ValidationException $e) {
            return $this->fieldError($e, 'convert');
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return $this->actionError($e, 'convert');
        }

        return redirect()->route('wallet.index')
            ->with('status', __('messages.wallet.convert.done'))
            ->with('status_section', 'convert');
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:255'],
        ], [
            'amount.required' => __('errors.validation.required'),
            'amount.regex' => __('errors.wallet.invalid_amount'),
            'bank_code.required' => __('errors.validation.required'),
            'account_number.required' => __('errors.validation.required'),
            'account_holder.required' => __('errors.validation.required'),
        ]);

        $token = $this->tokens->accessToken();
        $companyId = (string) ($this->tokens->activeCompany()['id'] ?? '');

        try {
            $this->wallet->withdraw($token, $companyId, $validated);
        } catch (ValidationException $e) {
            return $this->fieldError($e, 'withdraw');
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return $this->actionError($e, 'withdraw');
        }

        return redirect()->route('wallet.index')
            ->with('status', __('messages.wallet.withdraw.requested'))
            ->with('status_section', 'withdraw');
    }

    /**
     * Shared amount validation for top-up/convert — a positive IDR decimal string.
     *
     * @return array{amount:string}
     */
    private function validateAmount(Request $request, string $section): array
    {
        return $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/', 'not_in:0,0.0,0.00'],
        ], [
            'amount.required' => __('errors.validation.required'),
            'amount.regex' => __('errors.wallet.invalid_amount'),
            'amount.not_in' => __('errors.wallet.invalid_amount'),
        ]);
    }

    private function fieldError(ValidationException $e, string $section): RedirectResponse
    {
        return back()
            ->withErrors($e->errors() ?: ['amount' => __('errors.validation_failed')])
            ->withInput()
            ->with('error_section', $section);
    }

    private function actionError(TekomataApiException $e, string $section): RedirectResponse
    {
        return back()
            ->withErrors(['wallet_action' => $e->localizedMessage()])
            ->withInput()
            ->with('error_section', $section);
    }
}
