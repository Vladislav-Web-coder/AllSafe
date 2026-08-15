<?php

namespace App\Application\Analysis\UseCases;

use App\Application\Analysis\Commands\StartDocumentAnalysisCommand;
use App\Domain\Analysis\Entities\AnalysisRun;
use App\Domain\Analysis\Enums\AnalysisStatus;
use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Jobs\AnalyzeDocumentJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartDocumentAnalysisUseCase
{
    public function __construct(
        private AnalysisRunRepositoryInterface $analysisRuns,
        private DocumentRepositoryInterface $documents,
    ) {}

    public function handle(StartDocumentAnalysisCommand $command): AnalysisRun
    {
        $document = $command->document;

        if (! $document->current_version_id) {
            throw ValidationException::withMessages([
                'document' => ['Перед анализом необходимо загрузить версию документа.'],
            ]);
        }

        $latestRun = $this->analysisRuns->findLatestForDocument($document->id);

        if ($latestRun && $latestRun->status->isActive()) {
            throw ValidationException::withMessages([
                'document' => ['Анализ документа уже запущен.'],
            ]);
        }

        $analysisRun = DB::connection('pgsql_core')->transaction(function () use ($command, $document) {
            $analysisRun = $this->analysisRuns->create([
                'document_id' => $document->id,
                'document_version_id' => $document->current_version_id,
                'organization_id' => $document->organization_id,
                'status' => AnalysisStatus::Pending,
                'created_by' => $command->userId,
            ]);

            $this->documents->update($document, [
                'status' => DocumentStatus::Queued,
                'updated_by' => $command->userId,
            ]);

            return $analysisRun;
        });

        AnalyzeDocumentJob::dispatch($analysisRun->id)
            ->onQueue('documents.analysis');

        return $analysisRun;
    }
}
