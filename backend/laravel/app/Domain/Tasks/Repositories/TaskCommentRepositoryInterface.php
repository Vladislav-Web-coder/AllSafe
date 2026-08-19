<?php

namespace App\Domain\Tasks\Repositories;

use App\Domain\Tasks\Entities\TaskComment;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpWord\Element\Comment;

interface TaskCommentRepositoryInterface
{
    public function create(array $data): TaskComment;

    public function listForTask(int $taskId): Collection;
    public function findById(int $id): ?TaskComment;
    public function delete(TaskComment $comment): void;
}
