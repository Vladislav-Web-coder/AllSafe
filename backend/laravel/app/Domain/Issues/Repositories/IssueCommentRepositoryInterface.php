<?php

namespace App\Domain\Issues\Repositories;

use App\Domain\Issues\Entities\IssueComment;
use Illuminate\Database\Eloquent\Collection;

interface IssueCommentRepositoryInterface
{
    public function create(array $data): IssueComment;

    public function listForIssue(int $issueId): Collection;
}
