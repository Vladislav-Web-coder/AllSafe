<?php

namespace App\Domain\Issues\Repositories;

use App\Domain\Issues\Entities\IssueHistory;
use Illuminate\Database\Eloquent\Collection;

interface IssueHistoryRepositoryInterface
{
    public function create(array $data): IssueHistory;

    public function listForIssue(int $issueId): Collection;
}
