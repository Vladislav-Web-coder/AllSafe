<?php

namespace App\Application\Tasks\Commands;

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskSourceType;

class CreateTaskCommand
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly TaskPriority $priority = TaskPriority::Medium,
        public readonly TaskSourceType $sourceType = TaskSourceType::Manual,
        public readonly ?int $documentIssueId = null,
        public readonly ?int $documentId = null,
        public readonly ?int $assignedTo = null,
        public readonly ?string $dueDate = null,
        public readonly ?int $createdBy = null,
    ) {}
}
