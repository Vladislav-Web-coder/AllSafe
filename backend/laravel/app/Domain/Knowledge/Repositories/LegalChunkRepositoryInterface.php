<?php

namespace App\Domain\Knowledge\Repositories;

use App\Domain\Knowledge\Entities\LegalChunk;
use Illuminate\Database\Eloquent\Collection;

interface LegalChunkRepositoryInterface
{
    public function create(array $data): LegalChunk;

    public function deleteBySource(int $legalSourceId): void;

    public function searchSimilar(array $embedding, int $limit = 5): Collection;
}
