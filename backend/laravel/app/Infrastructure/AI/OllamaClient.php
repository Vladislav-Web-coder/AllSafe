<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Prompts\DocumentAnalysisPrompt;
use App\Infrastructure\AI\Support\AnalysisResultNormalizer;
use App\Infrastructure\AI\Support\LlmJsonParser;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaClient implements AiClientInterface
{
    public function __construct(
        private AnalysisResultNormalizer $normalizer,
    ) {}

    public function analyzeDocument(array $payload): array
    {
        $baseUrl = rtrim(config('ai.ollama.base_url'), '/');
        $model = config('ai.ollama.model');
        $temperature = (float) config('ai.ollama.temperature', 0.1);
        $timeout = (int) config('ai.ollama.timeout', 720);

        $response = Http::timeout($timeout)->post("{$baseUrl}/api/chat", [
            'model' => $model,
            'stream' => false,
            'format' => 'json',
            'options' => [
                'temperature' => $temperature,
            ],
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
                'Ollama request failed: ' . $response->body()
            );
        }

        $content = $response->json('message.content');

        if (! is_string($content)) {
            throw new RuntimeException('Ollama returned unexpected response format.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Ollama returned invalid JSON.');
        }

        return $this->normalizer->normalize($decoded, 'ollama', $model);
    }
    public function generateDocumentContent(string $prompt): array
    {
        $baseUrl = rtrim(config('ai.ollama.base_url'), '/');
        $model = config('ai.ollama.model');
        $timeout = (int) config('ai.ollama.timeout', 600);

        $response = Http::timeout($timeout)->post("{$baseUrl}/api/generate", [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.3,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Ollama generation failed: ' . $response->body());
        }

        $content = $response->json('response');

        if (! is_string($content)) {
            throw new RuntimeException('Ollama returned unexpected response.');
        }

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
