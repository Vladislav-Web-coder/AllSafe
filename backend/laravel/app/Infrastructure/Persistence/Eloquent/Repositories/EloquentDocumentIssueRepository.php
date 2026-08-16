<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Analysis\Entities\DocumentIssue;
use App\Domain\Analysis\Enums\IssueStatus;
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

    public function findById(int $id): ?DocumentIssue
    {
        return DocumentIssue::query()
            ->with(['document', 'comments', 'history'])
            ->find($id);
    }

    public function update(DocumentIssue $issue, array $data): DocumentIssue
    {
        $issue->update($data);

        return $issue->refresh();
    }

    public function listForDocument(int $documentId): Collection
    {
        return DocumentIssue::query()
            ->where('document_id', $documentId)
            ->with('comments')
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

    public function listForOrganization(int $organizationId): Collection
    {
        return DocumentIssue::query()
            ->where('organization_id', $organizationId)
            ->with('document')
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

    public function listOpenForOrganization(int $organizationId): Collection
    {
        return DocumentIssue::query()
            ->where('organization_id', $organizationId)
            ->whereIn('status', [IssueStatus::Open, IssueStatus::Accepted])
            ->with('document')
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")
            ->get();
    }
}
