<?php

namespace App\Services\Tekomata;

class InboxApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * @param  array<string,mixed>  $filters  channel, status, kind, assignee, limit, offset
     * @return array<string,mixed>
     */
    public function conversations(string $token, array $filters = []): array
    {
        return $this->client->get('/api/v1/inbox/conversations', $filters, $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function conversation(string $token, string $id): array
    {
        return $this->client->get('/api/v1/inbox/conversations/'.$id, token: $token)['data'] ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function messages(string $token, string $id, int $limit = 50, int $offset = 0): array
    {
        return $this->client->get('/api/v1/inbox/conversations/'.$id.'/messages', [
            'limit' => $limit,
            'offset' => $offset,
        ], $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data  body, attachments?, reply_to_message_id?
     * @return array<string,mixed>
     */
    public function reply(string $token, string $id, array $data): array
    {
        return $this->client->post('/api/v1/inbox/conversations/'.$id.'/reply', $data, $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function assign(string $token, string $id, ?string $assigneeUserId): array
    {
        return $this->client->post('/api/v1/inbox/conversations/'.$id.'/assign', [
            'assignee_user_id' => $assigneeUserId,
        ], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function updateStatus(string $token, string $id, string $status): array
    {
        return $this->client->patch('/api/v1/inbox/conversations/'.$id.'/status', [
            'status' => $status,
        ], $token)['data'] ?? [];
    }

    /** @return array<string,mixed> */
    public function addNote(string $token, string $id, string $body): array
    {
        return $this->client->post('/api/v1/inbox/conversations/'.$id.'/notes', [
            'body' => $body,
        ], $token)['data'] ?? [];
    }

    /** Advance read marker — POST returns 204. */
    public function markRead(string $token, string $id): void
    {
        $this->client->post('/api/v1/inbox/conversations/'.$id.'/read', [], $token);
    }

    /** Ephemeral typing signal — POST returns 204. */
    public function sendTyping(string $token, string $id): void
    {
        $this->client->post('/api/v1/inbox/conversations/'.$id.'/typing', [], $token);
    }

    /** Agent takes over from AI — sets ai_active=false, assigns to caller. */
    public function takeover(string $token, string $id): array
    {
        return $this->client->post('/api/v1/inbox/conversations/'.$id.'/takeover', [], $token)['data'] ?? [];
    }

    /** Hand conversation back to AI — sets ai_active=true, unassigns. */
    public function handback(string $token, string $id): array
    {
        return $this->client->post('/api/v1/inbox/conversations/'.$id.'/handback', [], $token)['data'] ?? [];
    }
}
