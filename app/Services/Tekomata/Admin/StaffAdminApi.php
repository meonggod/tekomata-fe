<?php

namespace App\Services\Tekomata\Admin;

use App\Services\Tekomata\TekomataClient;

/**
 * Staff-management + audit-log endpoints of the foundation, behind the staff
 * guard (`/api/v1/internal/*`). Listing staff and reading the audit log is open
 * to any staff; inviting a new staff member (`POST /internal/staff`) is
 * superadmin-only. Staff are invited by email + role and set their password from
 * an emailed link (no password is ever sent here).
 */
class StaffAdminApi
{
    public function __construct(private readonly TekomataClient $client) {}

    /** @return array<int,array<string,mixed>> */
    public function staff(string $token): array
    {
        return $this->client->get('/api/v1/internal/staff', [], $token)['data']['staff'] ?? [];
    }

    /**
     * Invite a staff member (email + role); the API emails a set-password link.
     * A duplicate email is rejected with `staff.email_taken`.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function createStaff(string $token, array $data): array
    {
        return $this->client->post('/api/v1/internal/staff', $data, $token)['data'] ?? [];
    }

    /**
     * The config-change audit trail: who changed what (staff id + email, action,
     * entity, status) for every mutating `/internal/*` request.
     *
     * @param  array<string,mixed>  $query
     * @return array<int,array<string,mixed>>
     */
    public function auditLog(string $token, array $query = []): array
    {
        return $this->client->get('/api/v1/internal/audit-log', $query, $token)['data']['entries'] ?? [];
    }
}
