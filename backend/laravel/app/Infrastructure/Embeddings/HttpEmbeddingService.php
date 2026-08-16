<?php

namespace App\Infrastructure\Embeddings;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpEmbeddingService implements EmbeddingServiceInterface
{
    public function embed(string $text): array
    {
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
}
