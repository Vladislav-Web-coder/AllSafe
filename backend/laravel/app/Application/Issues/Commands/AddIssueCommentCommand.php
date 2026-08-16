<?php

namespace App\Application\Issues\Commands;

class AddIssueCommentCommand
{
    public function __construct(
        public readonly int $issueId,
        public readonly int $userId,
        public readonly string $content,
    ) {}
}
