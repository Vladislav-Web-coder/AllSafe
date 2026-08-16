<?php

namespace App\Jobs;

use App\Domain\Analysis\Enums\AnalysisStatus;
use App\Domain\Analysis\Enums\IssueSeverity;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Analysis\Services\AnalysisVersionService;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use App\Domain\Knowledge\Repositories\LegalChunkRepositoryInterface;
use App\Infrastructure\AI\AiClientInterface;
use App\Infrastructure\Embeddings\EmbeddingServiceInterface;
use App\Infrastructure\Parsing\DocumentTextExtractorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AnalyzeDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $analysisRunId,
    )
    {}

    public function handle(
        AnalysisRunRepositoryInterface     $analysisRuns,
        DocumentRepositoryInterface        $documents,
        DocumentVersionRepositoryInterface $versions,
        DocumentIssueRepositoryInterface   $issues,
        AiClientInterface                  $ai,
        DocumentTextExtractorInterface     $textExtractor,
        EmbeddingServiceInterface          $embeddingService,
        LegalChunkRepositoryInterface      $legalChunks,
        AnalysisVersionService             $versionService,
    ): void
    {
        $run = $analysisRuns->findById($this->analysisRunId);

        if (!$run) {
            return;
        }

        $document = $run->document;

        try {
            // Обновляем статусы
            $analysisRuns->update($run, [
                'status' => AnalysisStatus::Processing,
                'started_at' => now(),
            ]);

            $documents->update($document, [
                'status' => DocumentStatus::Analyzing,
            ]);

            // Получаем версию документа
            $version = $versions->findById((int)$run->document_version_id);

            if (!$version) {
                throw new \RuntimeException('Версия документа не найдена.');
            }

            Log::info('AnalyzeDocumentJob: version loaded', [
                'version_id' => $version->id,
                'file_path' => $version->file_path,
                'parsed_text_path' => $version->parsed_text_path,
                'storage_disk' => $version->storage_disk,
            ]);

            $disk = $version->storage_disk ?: 'minio';

            // Извлекаем текст
            $fullText = null;

            if ($version->parsed_text_path) {
                $fullText = Storage::disk($disk)->get($version->parsed_text_path);

                Log::info('AnalyzeDocumentJob: using existing parsed text', [
                    'parsed_text_path' => $version->parsed_text_path,
                    'text_length' => mb_strlen($fullText ?? ''),
                ]);
            }

            if (!$fullText) {
                $fileContent = Storage::disk($disk)->get($version->file_path);

                if (!$fileContent) {
                    throw new \RuntimeException('Не удалось прочитать файл документа.');
                }

                $extension = pathinfo($version->file_name, PATHINFO_EXTENSION);

                $fullText = $textExtractor->extract($fileContent, $extension);

                if (trim($fullText) === '') {
                    throw new \RuntimeException('Не удалось извлечь текст из документа.');
                }

                $parsedPath = dirname($version->file_path) . '/parsed.txt';

                Storage::disk($disk)->put($parsedPath, $fullText);

                // Сохраняем parsed_text_path
                $version = $versions->update($version, [
                    'parsed_text_path' => $parsedPath,
                ]);

                Log::info('AnalyzeDocumentJob: parsed_text_path saved', [
                    'version_id' => $version->id,
                    'parsed_text_path' => $parsedPath,
                    'text_length' => mb_strlen($fullText),
                ]);
            }

            // RAG: создаём embedding и ищем релевантные НПА
            $maxEmbeddingChars = 4000;
            $textForEmbedding = mb_substr($fullText, 0, $maxEmbeddingChars);

            $legalContext = [];

            try {
                $documentEmbedding = $embeddingService->embed($textForEmbedding);

                $relevantChunks = $legalChunks->searchSimilar($documentEmbedding, 10);

                Log::info('AnalyzeDocumentJob: RAG search results', [
                    'document_id' => $document->id,
                    'chunks_found' => $relevantChunks->count(),
                ]);

                $legalContext = $relevantChunks->map(function ($chunk) {
                    return [
                        'source_title' => $chunk->source?->title,
                        'source_number' => $chunk->source?->number,
                        'article' => $chunk->article,
                        'part' => $chunk->part,
                        'content' => $chunk->content,
                    ];
                })->toArray();
            } catch (Throwable $e) {
                Log::warning('AnalyzeDocumentJob: RAG search failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);

                $legalContext = [];
            }

            // Формируем payload для AI
            $maxChars = (int)config('ai.analysis.max_chars', 20000);
            $truncatedText = mb_substr($fullText, 0, $maxChars);

            $document->load('type');

            $payload = [
                'document_id' => $document->id,
                'organization_id' => $document->organization_id,
                'document_type_code' => $document->type?->code,
                'document_type_name' => $document->type?->name,
                'title' => $document->title,
                'document_text' => $truncatedText,
                'legal_context' => $legalContext,
            ];

            // Вызываем AI
            $result = $ai->analyzeDocument($payload);

            // Получаем версии для записи
            $analysisVersions = $versionService->getAllVersions();

            // Сохраняем результаты
            DB::connection('pgsql_core')->transaction(function () use (
                $analysisRuns,
                $documents,
                $issues,
                $run,
                $document,
                $result,
                $analysisVersions,
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
                    'model_provider' => $result['model_provider'] ?? 'unknown',
                    'model_name' => $result['model_name'] ?? 'unknown',
                    'finished_at' => now(),
                    'error_message' => null,

                    // Версионирование
                    'prompt_version' => $analysisVersions['prompt_version'],
                    'knowledge_base_version' => $analysisVersions['knowledge_base_version'],
                    'requirements_version' => $analysisVersions['requirements_version'],
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
