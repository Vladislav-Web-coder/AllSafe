<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create($data);
    }

    public function paginate(
        int $perPage = 50,
        ?int $organizationId = null,
        ?string $action = null,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $query = AuditLog::query()
            ->orderBy('created_at', 'desc');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        if ($action !== null) {
            $query->where('action', 'like', "{$action}%");
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($subjectType !== null) {
            $query->where('subject_type', $subjectType);
        }

        if ($subjectId !== null) {
            $query->where('subject_id', $subjectId);
        }

        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        return $query->paginate($perPage);
    }
}
