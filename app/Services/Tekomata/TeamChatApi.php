<?php

namespace App\Services\Tekomata;

class TeamChatApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<string,mixed> */
    public function conversations(string $token): array
    {
        return $this->client->get('/api/v1/team/conversations', [], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function messages(string $token, string $id, int $limit = 50, int $offset = 0): array
    {
        return $this->client->get('/api/v1/team/conversations/'.$id.'/messages', [
            'limit' => $limit,
            'offset' => $offset,
        ], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function sendMessage(string $token, string $id, string $body): array
    {
        return $this->client->post('/api/v1/team/conversations/'.$id.'/messages', [
            'body' => $body,
        ], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function createDirect(string $token, string $userId): array
    {
        return $this->client->post('/api/v1/team/conversations', [
            'scope' => 'direct',
            'user_id' => $userId,
        ], $token)['data'] ?? [];
    }

    /**
     * @param  string[]  $memberUserIds
     * @return array<string,mixed>
     */
    public function createGroup(string $token, string $title, array $memberUserIds): array
    {
        return $this->client->post('/api/v1/team/conversations', [
            'scope' => 'group',
            'title' => $title,
            'member_user_ids' => $memberUserIds,
        ], $token)['data'] ?? [];
    }

    /** @param  string[]  $memberUserIds */
    public function addMembers(string $token, string $id, array $memberUserIds): void
    {
        $this->client->post('/api/v1/team/conversations/'.$id.'/members', [
            'member_user_ids' => $memberUserIds,
        ], $token);
    }
}
