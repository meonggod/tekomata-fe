<?php

namespace App\Services\Tekomata\Admin;

use App\Services\Tekomata\TekomataClient;

/**
 * CS-assistant review-queue + platform knowledge endpoints, behind the staff
 * guard (`/api/v1/internal/*`). The assistant records every question with an
 * answered / low-confidence flag; this surfaces the ones worth reviewing and lets
 * staff grow the platform-level feature/pricing knowledge base (about tekomata's
 * own product — not per-company). These are operational (non-money) actions, so
 * any staff incl. view-only `ops` may use them.
 */
class CsReviewApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * The review queue. `status=needs_review` (default upstream) returns the
     * not-yet-reviewed unanswered / low-confidence questions, each with its
     * `surface` and, when authenticated, the asking `user_id` / `company_id`.
     *
     * @param  array<string,mixed>  $query
     * @return array<int,array<string,mixed>>
     */
    public function questions(string $token, array $query = []): array
    {
        return $this->client->get('/api/v1/internal/cs-questions', $query, $token)['data']['questions'] ?? [];
    }

    /** Mark a question reviewed (`204`; unknown id → `404`). */
    public function markReviewed(string $token, string $id): void
    {
        $this->client->post('/api/v1/internal/cs-questions/'.$id.'/review', [], $token);
    }

    // ---- Knowledge base -----------------------------------------------------

    /** @return array<int,array<string,mixed>> */
    public function knowledgeEntries(string $token): array
    {
        return $this->client->get('/api/v1/internal/knowledge-entries', [], $token)['data']['entries'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function createKnowledge(string $token, array $data): array
    {
        return $this->client->post('/api/v1/internal/knowledge-entries', $data, $token)['data'] ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function updateKnowledge(string $token, string $id, array $data): array
    {
        return $this->client->put('/api/v1/internal/knowledge-entries/'.$id, $data, $token)['data'] ?? [];
    }
}
