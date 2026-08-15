<?php

namespace App\Jobs;

use App\Domain\Analysis\Enums\AnalysisStatus;
use App\Domain\Analysis\Enums\IssueSeverity;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Infrastructure\AI\AiClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class AnalyzeDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $analysisRunId,
    ) {}

    public function handle(
        AnalysisRunRepositoryInterface $analysisRuns,
        DocumentRepositoryInterface $documents,
        DocumentIssueRepositoryInterface $issues,
        AiClientInterface $ai,
    ): void {
        $run = $analysisRuns->findById($this->analysisRunId);

        if (! $run) {
            return;
        }

        $document = $run->document;

        try {
            $analysisRuns->update($run, [
                'status' => AnalysisStatus::Processing,
                'started_at' => now(),
            ]);

            $documents->update($document, [
                'status' => DocumentStatus::Analyzing,
            ]);

            $payload = [
                'document_id' => $document->id,
                'organization_id' => $document->organization_id,
                'document_type_id' => $document->document_type_id,
                'document_version_id' => $run->document_version_id,
                'title' => $document->title,
            ];

            $result = $ai->analyzeDocument($payload);

            DB::connection('pgsql_core')->transaction(function () use (
                $analysisRuns,
                $documents,
                $issues,
                $run,
                $document,
                $result,
            ) {
                $issues->deleteByRun($run->id);

                foreach ($result['issues'] ?? [] as $issue) {
                    $severity = IssueSeverity::tryFrom($issue['severity'] ?? '')
                        ?? IssueSeverity::Info;

                    $issues->create([
                        'analysis_run_id' => $run->id,
                        'document_id' => $document->id,
                        'document_version_id' => $run->document_version_id,
                        'organization_id' => $document->organization_id,
                        'requirement_code' => $issue['requirement_code'] ?? null,
                        'severity' => $severity,
                        'title' => $issue['title'] ?? 'Замечание',
                        'description' => $issue['description'] ?? null,
                        'recommendation' => $issue['recommendation'] ?? null,
                        'legal_basis_json' => $issue['legal_basis'] ?? [],
                        'section_code' => $issue['section_code'] ?? null,
                        'status' => IssueStatus::Open,
                    ]);
                }

                $analysisRuns->update($run, [
                    'status' => AnalysisStatus::Completed,
                    'score' => $result['score'] ?? null,
                    'summary_json' => $result['summary'] ?? null,
                    'missing_sections_json' => $result['missing_sections'] ?? [],
                    'legal_references_json' => $result['legal_references'] ?? [],
                    'model_provider' => $result['model_provider'] ?? 'fake',
                    'model_name' => $result['model_name'] ?? 'stub',
                    'finished_at' => now(),
                    'error_message' => null,
                ]);

                $documents->update($document, [
                    'status' => DocumentStatus::Completed,
                ]);
            });
        } catch (Throwable $e) {
            $analysisRuns->update($run, [
                'status' => AnalysisStatus::Failed,
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $documents->update($document, [
                'status' => DocumentStatus::Failed,
            ]);

            report($e);
        }
    }
}
