<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(int $id): ?Document
    {
        return Document::query()
            ->with('type')
            ->find($id);
    }

    public function create(array $data): Document
    {
        return Document::query()->create($data);
    }

    public function update(Document $document, array $data): Document
    {
        $document->update($data);

        return $document->refresh();
    }

    public function listForOrganization(int $organizationId): Collection
    {
        return Document::query()
            ->where('organization_id', $organizationId)
            ->with('type')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
