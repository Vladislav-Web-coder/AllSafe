<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Issues\Entities\IssueHistory;
use App\Domain\Issues\Repositories\IssueHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentIssueHistoryRepository implements IssueHistoryRepositoryInterface
{
    public function create(array $data): IssueHistory
    {
        return IssueHistory::query()->create($data);
    }

    public function listForIssue(int $issueId): Collection
    {
        return IssueHistory::query()
            ->where('document_issue_id', $issueId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
