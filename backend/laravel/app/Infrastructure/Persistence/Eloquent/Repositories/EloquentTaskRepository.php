<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::query()->create($data);
    }

    public function findById(int $id): ?Task
    {
        return Task::query()
            ->with(['issue', 'document', 'comments'])
            ->find($id);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function listForOrganization(int $organizationId): Collection
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->with('issue')
            ->orderByRaw("
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listForUser(int $organizationId, int $userId): Collection
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->where('assigned_to', $userId)
            ->orderByRaw("
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('due_date')
            ->get();
    }

    public function listOpenForOrganization(int $organizationId): Collection
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', [
                TaskStatus::New,
                TaskStatus::InProgress,
                TaskStatus::Blocked,
            ])
            ->orderByRaw("
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('due_date')
            ->get();
    }

    public function listOverdueForOrganization(int $organizationId): Collection
    {
        return Task::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', [
                TaskStatus::New,
                TaskStatus::InProgress,
                TaskStatus::Blocked,
            ])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();
    }

    public function countByStatus(int $organizationId): array
    {
        $counts = Task::query()
            ->where('organization_id', $organizationId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'new' => $counts['new'] ?? 0,
            'in_progress' => $counts['in_progress'] ?? 0,
            'blocked' => $counts['blocked'] ?? 0,
            'done' => $counts['done'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
            'total' => array_sum($counts),
        ];
    }
}
