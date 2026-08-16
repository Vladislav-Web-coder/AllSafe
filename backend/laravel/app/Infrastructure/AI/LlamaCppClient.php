<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Prompts\DocumentAnalysisPrompt;
use App\Infrastructure\AI\Support\AnalysisResultNormalizer;
use App\Infrastructure\AI\Support\LlmJsonParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LlamaCppClient implements AiClientInterface
{
    public function __construct(
        private AnalysisResultNormalizer $normalizer,
    ) {}

    public function analyzeDocument(array $payload): array
    {
        $baseUrl = rtrim((string) config('ai.llama_cpp.base_url'), '/');
        $apiKey = config('ai.llama_cpp.api_key');
        $model = (string) config('ai.llama_cpp.model', 'local');
        $temperature = (float) config('ai.llama_cpp.temperature', 0.1);
        $maxTokens = (int) config('ai.llama_cpp.max_tokens', 4096);
        $timeout = (int) config('ai.llama_cpp.timeout', 600);

        $request = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        if (! empty($apiKey)) {
            $request = $request->withToken((string) $apiKey);
        }

        $response = $request->post("{$baseUrl}/v1/chat/completions", [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'stream' => false,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => DocumentAnalysisPrompt::system(),
                ],
                [
                    'role' => 'user',
                    'content' => DocumentAnalysisPrompt::user($payload),
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'llama.cpp request failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new RuntimeException('llama.cpp returned unexpected response structure.');
        }

        // Логируем сырой ответ
        Log::info('LlamaCppClient analyzeDocument raw response', [
            'content_length' => mb_strlen($content),
            'content_start' => mb_substr($content, 0, 500),
            'content_end' => mb_substr($content, -300),
        ]);

        // Парсим JSON через надёжный парсер
        $parser = new LlmJsonParser();
        $decoded = $parser->parse($content);

        if (is_array($decoded)) {
            Log::info('LlamaCppClient analyzeDocument: JSON parsed successfully', [
                'has_score' => isset($decoded['score']),
                'has_issues' => isset($decoded['issues']),
                'issues_count' => count($decoded['issues'] ?? []),
            ]);

            return $this->normalizer->normalize($decoded, 'llama_cpp', $model);
        }

        // Если JSON не распарсился, пробуем извлечь данные из текста
        Log::warning('LlamaCppClient analyzeDocument: JSON not parsed, attempting text extraction');

        $extracted = $this->extractAnalysisFromText($content, $payload);

        if ($extracted !== null) {
            return $extracted;
        }

        // Если ничего не помогло, бросаем ошибку с содержимым для диагностики
        Log::error('LlamaCppClient analyzeDocument: failed to parse response', [
            'content_preview' => mb_substr($content, 0, 1000),
        ]);

        throw new RuntimeException(
            'llama.cpp returned invalid JSON in message content. ' .
            'Preview: ' . mb_substr($content, 0, 200)
        );
    }

    /**
     * Пытается извлечь результаты анализа из обычного текста.
     * Fallback для случаев, когда модель не вернула JSON.
     */
    private function extractAnalysisFromText(string $content, array $payload): ?array
    {
        // Если текст пустой — нечего извлекать
        if (trim($content) === '') {
            return null;
        }

        // Формируем минимальный результат из текста
        return [
            'score' => null,
            'summary' => [
                'total_checks' => 1,
                'passed' => 0,
                'failed' => 1,
                'warnings' => 0,
            ],
            'missing_sections' => [],
            'legal_references' => [],
            'issues' => [
                [
                    'requirement_code' => null,
                    'severity' => 'info',
                    'title' => 'Результат анализа от LLM',
                    'description' => mb_substr($content, 0, 2000),
                    'recommendation' => 'Требуется ручная проверка документа.',
                    'legal_basis' => [],
                    'section_code' => null,
                ],
            ],
            'model_provider' => 'llama_cpp',
            'model_name' => (string) config('ai.llama_cpp.model', 'local'),
        ];
    }

    public function generateDocumentContent(string $prompt): array
    {
        $baseUrl = rtrim((string) config('ai.llama_cpp.base_url'), '/');
        $apiKey = config('ai.llama_cpp.api_key');
        $model = (string) config('ai.llama_cpp.model', 'local');
        $temperature = 0.3;
        $maxTokens = (int) config('ai.llama_cpp.max_tokens', 8192);
        $timeout = (int) config('ai.llama_cpp.timeout', 600);

        $request = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        if (! empty($apiKey)) {
            $request = $request->withToken((string) $apiKey);
        }

        $response = $request->post("{$baseUrl}/v1/chat/completions", [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'stream' => false,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'llama.cpp generation failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new RuntimeException('llama.cpp returned unexpected response structure.');
        }

        Log::info('LlamaCppClient generateDocumentContent raw response', [
            'content_length' => mb_strlen($content),
            'content_start' => mb_substr($content, 0, 200),
            'content_end' => mb_substr($content, -200),
        ]);

        $parser = new LlmJsonParser();
        $decoded = $parser->parse($content);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'content' => $content,
            'sections' => [],
        ];
    }
}
