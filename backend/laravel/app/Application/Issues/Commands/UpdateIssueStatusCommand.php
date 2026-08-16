<?php

namespace App\Application\Issues\Commands;

use App\Domain\Analysis\Enums\IssueStatus;

class UpdateIssueStatusCommand
{
    public function __construct(
        public readonly int $issueId,
        public readonly IssueStatus $newStatus,
        public readonly int $userId,
        public readonly ?string $comment = null,
    ) {}
}
