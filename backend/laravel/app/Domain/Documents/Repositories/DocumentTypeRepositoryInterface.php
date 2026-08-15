<?php

namespace App\Domain\Documents\Repositories;

use App\Domain\Documents\Entities\DocumentType;
use Illuminate\Database\Eloquent\Collection;

interface DocumentTypeRepositoryInterface
{
    public function findById(int $id): ?DocumentType;

    public function findActiveByCode(string $code): ?DocumentType;

    public function getActive(): Collection;
}
