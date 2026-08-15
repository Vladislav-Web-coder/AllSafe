<?php

namespace App\Domain\Documents\Repositories;

use App\Domain\Documents\Entities\Document;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    public function findById(int $id): ?Document;

    public function create(array $data): Document;

    public function update(Document $document, array $data): Document;

    public function listForOrganization(int $organizationId): Collection;
}
