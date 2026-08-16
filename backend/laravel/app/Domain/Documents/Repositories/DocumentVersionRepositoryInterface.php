<?php

namespace App\Domain\Documents\Repositories;

use App\Domain\Documents\Entities\DocumentVersion;

interface DocumentVersionRepositoryInterface
{
    public function nextVersionNumber(int $documentId): int;

    public function create(array $data): DocumentVersion;

    public function findById(int $id): ?DocumentVersion;
    public function update(DocumentVersion $version, array $data): DocumentVersion;
}
