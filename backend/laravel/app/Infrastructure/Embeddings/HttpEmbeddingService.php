<?php

namespace App\Infrastructure\Embeddings;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpEmbeddingService implements EmbeddingServiceInterface
{
    /**
     * Максимальная длина текста для embedding.
     * Для sentence-transformers оптимально ~2000-4000 символов.
     */
    private const MAX_TEXT_LENGTH = 4000;

    public function embed(string $text): array
    {
        $text = $this->truncate($text);

        $baseUrl = rtrim((string) config('ai.embedding.base_url'), '/');
        $timeout = (int) config('ai.embedding.timeout', 60);

        $response = Http::timeout($timeout)->post("{$baseUrl}/embed", [
            'text' => $text,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Embedding service failed: ' . $response->body()
            );
        }

        $embedding = $response->json('embedding');

        if (! is_array($embedding)) {
            throw new RuntimeException('Embedding service returned invalid response.');
        }

        return $embedding;
    }

    public function embedBatch(array $texts): array
    {
        // Обрезаем каждый текст
        $texts = array_map(fn ($text) => $this->truncate($text), $texts);

        $baseUrl = rtrim((string) config('ai.embedding.base_url'), '/');
        $timeout = (int) config('ai.embedding.timeout', 120);

        $response = Http::timeout($timeout)->post("{$baseUrl}/embed_batch", [
            'texts' => $texts,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Embedding service batch failed: ' . $response->body()
            );
        }

        $embeddings = $response->json('embeddings');

        if (! is_array($embeddings)) {
            throw new RuntimeException('Embedding service returned invalid batch response.');
        }

        return $embeddings;
    }

    /**
     * Обрезает текст до максимальной длины.
     */
    private function truncate(string $text): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= self::MAX_TEXT_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH);
    }
}
