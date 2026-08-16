<?php

namespace App\Application\Tasks\Commands;

class AssignTaskCommand
{
    public function __construct(
        public readonly int $taskId,
        public readonly int $assignedTo,
        public readonly int $userId,
    ) {}
}
