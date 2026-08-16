<?php

namespace App\Application\Issues\Commands;

use App\Domain\Analysis\Enums\IssueStatus;

class BulkUpdateIssuesCommand
{
    public function __construct(
        public readonly array $issueIds,
        public readonly IssueStatus $newStatus,
        public readonly int $userId,
        public readonly ?string $comment = null,
    ) {}
}
