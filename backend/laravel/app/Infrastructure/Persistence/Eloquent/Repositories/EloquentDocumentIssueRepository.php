<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Analysis\Entities\DocumentIssue;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentIssueRepository implements DocumentIssueRepositoryInterface
{
    public function listForRun(int $analysisRunId): Collection
    {
        return DocumentIssue::query()
            ->where('analysis_run_id', $analysisRunId)
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): DocumentIssue
    {
        return DocumentIssue::query()->create($data);
    }

    public function deleteByRun(int $analysisRunId): void
    {
        DocumentIssue::query()
            ->where('analysis_run_id', $analysisRunId)
            ->delete();
    }
}
