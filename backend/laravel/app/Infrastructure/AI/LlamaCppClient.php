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
        $maxTokens = (int) config('ai.llama_cpp.max_tokens', 2048);
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

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('llama.cpp returned non-JSON response.');
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! is_string($content)) {
            throw new RuntimeException(
                'llama.cpp returned unexpected response structure.'
            );
        }

        $content = $this->stripMarkdownCodeFences($content);

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'llama.cpp returned invalid JSON in message content.'
            );
        }

        return $this->normalizer->normalize($decoded, 'llama_cpp', $model);
    }

    public function generateDocumentContent(string $prompt): array
    {
        $baseUrl = rtrim((string) config('ai.llama_cpp.base_url'), '/');
        $apiKey = config('ai.llama_cpp.api_key');
        $model = (string) config('ai.llama_cpp.model', 'local');
        $temperature = (float) config('ai.llama_cpp.temperature', 0.3);
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

        // Логируем сырой ответ
        Log::info('LlamaCppClient generateDocumentContent raw response', [
            'content_length' => mb_strlen($content),
            'content_start' => mb_substr($content, 0, 200),
            'content_end' => mb_substr($content, -200),
        ]);

        // Парсим JSON через надёжный парсер
        $parser = new LlmJsonParser();
        $decoded = $parser->parse($content);

        if (is_array($decoded)) {
            Log::info('LlamaCppClient generateDocumentContent: JSON parsed successfully', [
                'has_content' => isset($decoded['content']),
                'has_sections' => isset($decoded['sections']),
                'sections_count' => count($decoded['sections'] ?? []),
            ]);

            return $decoded;
        }

        // Если JSON не распарсился, возвращаем как единый контент
        Log::warning('LlamaCppClient generateDocumentContent: JSON not parsed, returning as content');

        return [
            'content' => $content,
            'sections' => [],
        ];
    }

    private function stripMarkdownCodeFences(string $content): string
    {
        $content = trim($content);

        $content = (string) preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = (string) preg_replace('/\s*```$/', '', $content);

        return trim($content);
    }
}
