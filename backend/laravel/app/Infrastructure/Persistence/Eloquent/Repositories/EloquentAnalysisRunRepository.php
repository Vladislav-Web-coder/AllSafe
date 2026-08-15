<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Analysis\Entities\AnalysisRun;
use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;

class EloquentAnalysisRunRepository implements AnalysisRunRepositoryInterface
{
    public function findById(int $id): ?AnalysisRun
    {
        return AnalysisRun::query()
            ->with('document')
            ->find($id);
    }

    public function findLatestForDocument(int $documentId): ?AnalysisRun
    {
        return AnalysisRun::query()
            ->where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function create(array $data): AnalysisRun
    {
        return AnalysisRun::query()->create($data);
    }

    public function update(AnalysisRun $analysisRun, array $data): AnalysisRun
    {
        $analysisRun->update($data);

        return $analysisRun->refresh();
    }
}
