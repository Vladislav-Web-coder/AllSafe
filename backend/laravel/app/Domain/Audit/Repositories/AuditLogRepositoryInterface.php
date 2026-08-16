<?php

namespace App\Domain\Audit\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog;

    public function paginate(
        int $perPage = 50,
        ?int $organizationId = null,
        ?string $action = null,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator;
}
