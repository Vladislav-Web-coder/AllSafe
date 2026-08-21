<?php

namespace App\Domain\Knowledge\Services;

interface EmbeddingServiceInterface
{
    public function generate(string $text): array;

    public function generateBatch(array $texts): array;
}
