<?php

namespace App\Services\Tekomata;

/**
 * Prepaid IDR wallet endpoints of the Go API. One thin method per call.
 *
 * Every path is tenant-scoped: the company id in the path MUST equal the active
 * company carried in the JWT (the API returns 403 otherwise). Money values are
 * decimal strings (IDR) on both the wire and in the rendered panel — never floats.
 */
class WalletApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Both balances + a page of the bucket-tagged transaction ledger.
     *
     * @return array<string,mixed>
     */
    public function get(string $token, string $companyId, int $limit = 20, int $offset = 0): array
    {
        return $this->client->get(
            $this->base($companyId),
            ['limit' => $limit, 'offset' => $offset],
            $token,
        )['data'] ?? [];
    }

    /**
     * Open a top-up intent at the payment provider; the returned payment_url is
     * the provider checkout the owner is redirected to. The spendable balance is
     * credited by the provider webhook on confirmation, never here.
     *
     * @return array<string,mixed>
     */
    public function topup(string $token, string $companyId, string $amount): array
    {
        return $this->client->post(
            $this->base($companyId).'/topup',
            ['amount' => $amount],
            $token,
        )['data'] ?? [];
    }

    /**
     * Move reward funds into the spendable bucket so they can pay for tekomata.
     * Converted funds are no longer withdrawable. Returns the fresh balances.
     *
     * @return array<string,mixed>
     */
    public function convert(string $token, string $companyId, string $amount): array
    {
        return $this->client->post(
            $this->base($companyId).'/convert',
            ['amount' => $amount],
            $token,
        )['data'] ?? [];
    }

    /**
     * Cash out reward funds to a verified bank account (KYB gated). Settlement is
     * confirmed by the payout webhook.
     *
     * @param  array<string,mixed>  $data  amount, bank_code, account_number, account_holder
     * @return array<string,mixed>
     */
    public function withdraw(string $token, string $companyId, array $data): array
    {
        return $this->client->post(
            $this->base($companyId).'/withdraw',
            $data,
            $token,
        )['data'] ?? [];
    }

    private function base(string $companyId): string
    {
        return '/api/v1/companies/'.$companyId.'/wallet';
    }
}
