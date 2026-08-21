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
use App\Domain\Knowledge\Services\LegalSearchService;
use App\Domain\Notifications\Repositories\NotificationRepositoryInterface;
use App\Domain\Notifications\Services\NotificationService;
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
        LegalSearchService                 $legalSearchService,
        AnalysisVersionService             $versionService,
        NotificationService                $notificationService,
        \App\Domain\Organizations\Repositories\OrganizationRepositoryInterface $organizations,  // ← Добавлено
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

            $maxEmbeddingChars = 3000;
            $textForEmbedding = mb_substr($fullText, 0, $maxEmbeddingChars);

            $legalContext = [];

            try {
                // Получаем профиль организации для контекстного поиска
                $organization = $organizations->findById($document->organization_id);
                $profile = $organization?->profile?->toArray() ?? [];

                // Формируем поисковый запрос на основе текста документа
                $searchQuery = $this->buildSearchQuery($textForEmbedding, $document->type?->code);

                Log::info('AnalyzeDocumentJob: RAG search query', [
                    'document_id' => $document->id,
                    'search_query' => mb_substr($searchQuery, 0, 200),
                    'profile' => $profile,
                ]);

                // Используем hybrid search с контекстом организации
                $relevantChunks = $legalSearchService->searchWithContext(
                    query: $searchQuery,
                    organizationProfile: $profile,
                    limit: 8,
                );

                Log::info('AnalyzeDocumentJob: RAG search results', [
                    'document_id' => $document->id,
                    'chunks_found' => $relevantChunks->count(),
                    'top_scores' => $relevantChunks->take(3)->map(fn($c) => [
                        'reference' => $c->getReference(),
                        'score' => round($c->score, 4),
                    ])->toArray(),
                ]);

                // Форматируем контекст для LLM
                $legalContext = $this->formatLegalContext($relevantChunks);

            } catch (Throwable $e) {
                Log::warning('AnalyzeDocumentJob: RAG search failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);

                $legalContext = [];
            }

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
            $notificationService->notify(
                userId: $run->created_by,
                organizationId: $document->organization_id,
                type: 'analysis_completed',
                title: 'Анализ завершён',
                message: "Документ \"{$document->title}\" успешно проанализирован. Обнаружено замечаний: " . count($result['issues'] ?? []),
                linkType: 'document',
                linkId: $document->id,
            );

            // Уведомляем admin и security_officer о критических замечаниях
            $criticalIssues = array_filter(
                $result['issues'] ?? [],
                fn ($issue) => in_array($issue['severity'] ?? '', ['critical', 'high'])
            );

            if (! empty($criticalIssues)) {
                $notificationService->notifyByRoles(
                    organizationId: $document->organization_id,
                    roles: ['owner', 'admin', 'security_officer'],
                    type: 'issue_added',
                    title: 'Критические замечания',
                    message: "В документе \"{$document->title}\" обнаружено критических замечаний: " . count($criticalIssues),
                    linkType: 'document',
                    linkId: $document->id,
                );
            }
        } catch (Throwable $e) {
            $analysisRuns->update($run, [
                'status' => AnalysisStatus::Failed,
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $documents->update($document, [
                'status' => DocumentStatus::Failed,
            ]);

            $notificationService->notify(
                userId: $run->created_by,
                organizationId: $document->organization_id,
                type: 'analysis_failed',
                title: 'Ошибка анализа',
                message: "Не удалось проанализировать документ \"{$document->title}\": {$e->getMessage()}",
                linkType: 'document',
                linkId: $document->id,
            );

            report($e);
        }
    }

    /**
     * Формирует поисковый запрос на основе текста документа.
     */
    private function buildSearchQuery(string $text, ?string $documentTypeCode): string
    {
        // Извлекаем ключевые фразы из начала документа
        $sentences = preg_split('/[.!?]+/', mb_substr($text, 0, 2000));
        $keyPhrases = [];

        // Берём первые 3-5 содержательных предложений
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) > 30 && mb_strlen($sentence) < 300) {
                $keyPhrases[] = $sentence;
                if (count($keyPhrases) >= 5) break;
            }
        }

        $query = implode(' ', $keyPhrases);

        // Добавляем контекст типа документа
        if ($documentTypeCode) {
            $typeKeywords = [
                'pd_policy' => 'политика обработки персональных данных',
                'consent_form' => 'согласие на обработку персональных данных',
                'security_policy' => 'политика безопасности информации',
                'data_protection' => 'защита персональных данных',
                'privacy_policy' => 'политика конфиденциальности',
            ];

            if (isset($typeKeywords[$documentTypeCode])) {
                $query = $typeKeywords[$documentTypeCode] . ' ' . $query;
            }
        }

        return mb_substr($query, 0, 1000);
    }
    private function formatLegalContext($chunks): array
    {
        return $chunks->map(function ($chunk) {
            return [
                'reference' => $chunk->getReference(),
                'source_title' => $chunk->source?->title,
                'source_number' => $chunk->source?->number,
                'article' => $chunk->article,
                'part' => $chunk->part,
                'clause' => $chunk->clause,
                'content' => mb_substr($chunk->content, 0, 800),
                'relevance_score' => round($chunk->score, 4),
            ];
        })->toArray();
    }
}
