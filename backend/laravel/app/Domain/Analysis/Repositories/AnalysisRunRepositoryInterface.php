<?php

namespace App\Domain\Analysis\Repositories;

use App\Domain\Analysis\Entities\AnalysisRun;

interface AnalysisRunRepositoryInterface
{
    public function findById(int $id): ?AnalysisRun;

    public function findLatestForDocument(int $documentId): ?AnalysisRun;

    public function create(array $data): AnalysisRun;

    public function update(AnalysisRun $analysisRun, array $data): AnalysisRun;
}
