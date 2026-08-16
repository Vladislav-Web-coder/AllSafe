<?php

namespace App\Domain\Tasks\Repositories;

use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function create(array $data): Task;

    public function findById(int $id): ?Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): void;

    public function listForOrganization(int $organizationId): Collection;

    public function listForUser(int $organizationId, int $userId): Collection;

    public function listOpenForOrganization(int $organizationId): Collection;

    public function listOverdueForOrganization(int $organizationId): Collection;

    public function countByStatus(int $organizationId): array;
}
