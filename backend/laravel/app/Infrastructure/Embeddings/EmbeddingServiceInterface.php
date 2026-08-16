<?php

namespace App\Infrastructure\Embeddings;

interface EmbeddingServiceInterface
{
    /**
     * @return float[]
     */
    public function embed(string $text): array;

    /**
     * @param string[] $texts
     * @return array<float[]>
     */
    public function embedBatch(array $texts): array;
}
