<?php

namespace App\Application\Tasks\Commands;

use App\Domain\Tasks\Enums\TaskStatus;

class UpdateTaskStatusCommand
{
    public function __construct(
        public readonly int $taskId,
        public readonly TaskStatus $newStatus,
        public readonly int $userId,
    ) {}
}
