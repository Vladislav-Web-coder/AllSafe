<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Documents\Entities\DocumentType;
use App\Domain\Documents\Repositories\DocumentTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDocumentTypeRepository implements DocumentTypeRepositoryInterface
{
    public function findById(int $id): ?DocumentType
    {
        return DocumentType::query()->find($id);
    }

    public function findActiveByCode(string $code): ?DocumentType
    {
        return DocumentType::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    public function getActive(): Collection
    {
        return DocumentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
