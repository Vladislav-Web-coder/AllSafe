<?php

namespace App\Application\Tasks\Commands;

class CreateTaskFromIssueCommand
{
    public function __construct(
        public readonly int $issueId,
        public readonly int $organizationId,
        public readonly int $userId,
        public readonly ?int $assignedTo = null,
        public readonly ?string $dueDate = null,
    ) {}
}
