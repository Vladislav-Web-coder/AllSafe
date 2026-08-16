<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Knowledge\Entities\LegalChunk;
use App\Domain\Knowledge\Repositories\LegalChunkRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentLegalChunkRepository implements LegalChunkRepositoryInterface
{
    public function create(array $data): LegalChunk
    {
        return LegalChunk::query()->create($data);
    }

    public function deleteBySource(int $legalSourceId): void
    {
        LegalChunk::query()
            ->where('legal_source_id', $legalSourceId)
            ->delete();
    }

    public function searchSimilar(array $embedding, int $limit = 5): Collection
    {
        $vectorString = '[' . implode(',', $embedding) . ']';

        return LegalChunk::query()
            ->select([
                'legal_chunks.*',
                DB::raw("embedding <=> '{$vectorString}'::vector AS distance"),
            ])
            ->whereNotNull('embedding')
            ->orderBy('distance')
            ->limit($limit)
            ->with('source')
            ->get();
    }
}
