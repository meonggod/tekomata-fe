<?php

namespace App\Services\Tekomata;

/**
 * Async catalog import (Excel/CSV → staged → explicit/auto apply) and catalog
 * browse for the active company. Every call is JWT-scoped; the active company
 * is resolved from the token, never the request.
 *
 * The import is non-blocking: {@see enqueue()} returns a job id immediately and
 * the worker parses/stages/applies in the background, pushing progress over SSE
 * (proxied by the controller). The methods below cover the full lifecycle —
 * toggle auto-apply, record conflict decisions, apply, retry failed rows,
 * discard, and read history / staged review data.
 */
class CatalogImportApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /**
     * Persist the upload to GCS and enqueue the parse/stage job. Returns
     * {job_id, status} immediately — no blocking on parsing or import.
     *
     * @return array<string,mixed>
     */
    public function enqueue(string $token, string $fileContents, string $fileName, bool $autoApply): array
    {
        $data = $this->client->postMultipart(
            '/api/v1/catalog/import',
            'file',
            $fileContents,
            $fileName,
            ['auto_apply' => $autoApply ? '1' : '0'],
            $token,
        );

        return $data['data'] ?? $data;
    }

    /**
     * Toggle "auto-apply when no errors" while the job is still pre-apply
     * (queued/parsing/staged). A clean already-staged job applies immediately.
     *
     * @return array<string,mixed>
     */
    public function toggleAutoApply(string $token, string $jobId, bool $autoApply): array
    {
        $data = $this->client->post("/api/v1/catalog/import/{$jobId}/auto-apply", ['auto_apply' => $autoApply], $token);

        return $data['data'] ?? $data;
    }

    /**
     * Record the owner's conflict-level decisions (one per unique new entity).
     *
     * @param  array<int,array{conflict_id:string,decision:string}>  $decisions
     */
    public function decisions(string $token, string $jobId, array $decisions): void
    {
        // Go API expects a bare JSON array body; wrap so the client's json mode
        // sends it as the top-level payload.
        $this->client->request('POST', "/api/v1/catalog/import/{$jobId}/decisions", ['json' => $decisions], $token);
    }

    /**
     * The explicit "create" action — commit the staged import. 422 with the
     * undecided conflicts if any remain pending.
     *
     * @return array<string,mixed>
     */
    public function apply(string $token, string $jobId): array
    {
        $data = $this->client->post("/api/v1/catalog/import/{$jobId}/apply", [], $token);

        return $data['data'] ?? $data;
    }

    /**
     * Re-attempt the rows that failed to commit (partial), or reparse from the
     * retained GCS file (catastrophic failed). Idempotent — upserts keyed by SKU.
     *
     * @return array<string,mixed>
     */
    public function retry(string $token, string $jobId): array
    {
        $data = $this->client->post("/api/v1/catalog/import/{$jobId}/retry", [], $token);

        return $data['data'] ?? $data;
    }

    /**
     * Abandon a staged import, or dismiss the retained failures of a partial job.
     */
    public function discard(string $token, string $jobId): void
    {
        $this->client->delete("/api/v1/catalog/import/{$jobId}", [], $token);
    }

    /**
     * Import history for the active company, newest first.
     *
     * @return array<int,array<string,mixed>>
     */
    public function history(string $token, int $limit = 20, int $offset = 0): array
    {
        $data = $this->client->get('/api/v1/catalog/import/history', ['limit' => $limit, 'offset' => $offset], $token);

        return $data['data']['jobs'] ?? $data['jobs'] ?? [];
    }

    /**
     * Review-panel data: the job, its conflicts, error rows, and (for a
     * partial/failed job) the retained apply-time failed rows.
     *
     * @return array<string,mixed>
     */
    public function staged(string $token, string $jobId): array
    {
        $data = $this->client->get("/api/v1/catalog/import/{$jobId}/staged", [], $token);

        return $data['data'] ?? $data;
    }

    /**
     * List products with per-warehouse stock and per-tier prices.
     *
     * @return array<int,array<string,mixed>>
     */
    public function browse(string $token, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $query = ['limit' => $limit, 'offset' => $offset];
        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->client->get('/api/v1/catalog', $query, $token)['data']['products'] ?? [];
    }
}
