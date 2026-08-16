<?php

namespace App\Domain\Tasks\Repositories;

use App\Domain\Tasks\Entities\TaskComment;
use Illuminate\Database\Eloquent\Collection;

interface TaskCommentRepositoryInterface
{
    public function create(array $data): TaskComment;

    public function listForTask(int $taskId): Collection;
}
