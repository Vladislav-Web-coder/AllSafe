<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Documents\Entities\DocumentVersion;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EloquentDocumentVersionRepository implements DocumentVersionRepositoryInterface
{
    public function nextVersionNumber(int $documentId): int
    {
        $max = DocumentVersion::query()
            ->where('document_id', $documentId)
            ->max('version_number');

        $next = ((int) $max) + 1;

        Log::info('DocumentVersionRepository: nextVersionNumber', [
            'document_id' => $documentId,
            'current_max' => $max,
            'next' => $next,
        ]);

        return $next;
    }

    public function create(array $data): DocumentVersion
    {
        Log::info('DocumentVersionRepository: create', [
            'document_id' => $data['document_id'] ?? null,
            'version_number' => $data['version_number'] ?? null,
            'file_name' => $data['file_name'] ?? null,
        ]);

        $version = DocumentVersion::query()->create($data);

        Log::info('DocumentVersionRepository: created', [
            'version_id' => $version->id,
            'document_id' => $version->document_id,
            'version_number' => $version->version_number,
        ]);

        return $version;
    }

    public function findById(int $id): ?DocumentVersion
    {
        return DocumentVersion::query()->find($id);
    }
    public function update(DocumentVersion $version, array $data): DocumentVersion
    {
        $version->forceFill($data)->save();

        return $version->refresh();
    }
    public function listForDocument(int $documentId)
    {
        return DocumentVersion::query()
            ->where('document_id', $documentId)
            ->orderBy('version_number', 'desc')
            ->get();
    }
}
