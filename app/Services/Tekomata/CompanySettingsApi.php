<?php

namespace App\Services\Tekomata;

class CompanySettingsApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<string,mixed> */
    public function getSettings(string $token): array
    {
        return $this->client->get('/api/v1/settings', token: $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function patchSettings(string $token, array $data): array
    {
        return $this->client->patch('/api/v1/settings', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function addEmail(string $token, array $data): array
    {
        return $this->client->post('/api/v1/settings/emails', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateEmail(string $token, string $id, array $data): array
    {
        return $this->client->patch('/api/v1/settings/emails/'.$id, $data, $token)['data'] ?? [];
    }

    public function deleteEmail(string $token, string $id): void
    {
        $this->client->delete('/api/v1/settings/emails/'.$id, token: $token);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function addWhatsapp(string $token, array $data): array
    {
        return $this->client->post('/api/v1/settings/whatsapp-numbers', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateWhatsapp(string $token, string $id, array $data): array
    {
        return $this->client->patch('/api/v1/settings/whatsapp-numbers/'.$id, $data, $token)['data'] ?? [];
    }

    public function deleteWhatsapp(string $token, string $id): void
    {
        $this->client->delete('/api/v1/settings/whatsapp-numbers/'.$id, token: $token);
    }
}
