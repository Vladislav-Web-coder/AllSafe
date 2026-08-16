<?php

namespace App\Infrastructure\Embeddings;

class FakeEmbeddingService implements EmbeddingServiceInterface
{
    public function embed(string $text): array
    {
        // Возвращаем фиктивный вектор размерности 384
        return array_fill(0, 384, 0.0);
    }

    public function embedBatch(array $texts): array
    {
        return array_map(fn ($text) => $this->embed($text), $texts);
    }
}
