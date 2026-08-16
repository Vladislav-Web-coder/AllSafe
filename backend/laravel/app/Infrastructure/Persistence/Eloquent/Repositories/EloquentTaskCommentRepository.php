<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Tasks\Entities\TaskComment;
use App\Domain\Tasks\Repositories\TaskCommentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskCommentRepository implements TaskCommentRepositoryInterface
{
    public function create(array $data): TaskComment
    {
        return TaskComment::query()->create($data);
    }

    public function listForTask(int $taskId): Collection
    {
        return TaskComment::query()
            ->where('task_id', $taskId)
            ->orderBy('created_at')
            ->get();
    }
}
