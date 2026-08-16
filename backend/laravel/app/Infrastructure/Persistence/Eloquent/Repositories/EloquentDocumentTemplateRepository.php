<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Generation\Entities\DocumentTemplate;
use App\Domain\Generation\Repositories\DocumentTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentTemplateRepository implements DocumentTemplateRepositoryInterface
{
    public function getActive(): Collection
    {
        return DocumentTemplate::query()
            ->where('is_active', true)
            ->with('documentType')
            ->get();
    }

    public function findById(int $id): ?DocumentTemplate
    {
        return DocumentTemplate::query()->find($id);
    }

    public function findByCode(string $code): ?DocumentTemplate
    {
        return DocumentTemplate::query()
            ->where('code', $code)
            ->first();
    }

    public function findByDocumentTypeId(int $documentTypeId): ?DocumentTemplate
    {
        return DocumentTemplate::query()
            ->where('document_type_id', $documentTypeId)
            ->where('is_active', true)
            ->first();
    }
}
