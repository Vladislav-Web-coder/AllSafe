<?php

namespace App\Domain\Generation\Repositories;

use App\Domain\Generation\Entities\DocumentTemplate;
use Illuminate\Database\Eloquent\Collection;

interface DocumentTemplateRepositoryInterface
{
    public function getActive(): Collection;

    public function findById(int $id): ?DocumentTemplate;

    public function findByCode(string $code): ?DocumentTemplate;

    public function findByDocumentTypeId(int $documentTypeId): ?DocumentTemplate;
}
