<?php

namespace App\Infrastructure\AI;

interface AiClientInterface
{
    public function analyzeDocument(array $payload): array;
}
