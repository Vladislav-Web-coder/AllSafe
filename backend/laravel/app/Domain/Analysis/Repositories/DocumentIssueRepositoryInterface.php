<?php

namespace App\Domain\Analysis\Repositories;

use App\Domain\Analysis\Entities\DocumentIssue;
use Illuminate\Database\Eloquent\Collection;

interface DocumentIssueRepositoryInterface
{
    public function listForRun(int $analysisRunId): Collection;

    public function create(array $data): DocumentIssue;

    public function deleteByRun(int $analysisRunId): void;

    public function findById(int $id): ?DocumentIssue;

    public function update(DocumentIssue $issue, array $data): DocumentIssue;

    public function listForDocument(int $documentId): Collection;

    public function listForOrganization(int $organizationId): Collection;

    public function listOpenForOrganization(int $organizationId): Collection;
}
