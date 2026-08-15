<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Documents\Entities\DocumentVersion;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;

class EloquentDocumentVersionRepository implements DocumentVersionRepositoryInterface
{
    public function nextVersionNumber(int $documentId): int
    {
        $max = DocumentVersion::query()
            ->where('document_id', $documentId)
            ->max('version_number');

        return (int) $max + 1;
    }

    public function create(array $data): DocumentVersion
    {
        return DocumentVersion::query()->create($data);
    }

    public function findById(int $id): ?DocumentVersion
    {
        return DocumentVersion::query()->find($id);
    }
}
