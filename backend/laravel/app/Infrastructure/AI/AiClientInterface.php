<?php

namespace App\Infrastructure\AI;

interface AiClientInterface
{
    /**
     * @param array $payload
     *   - document_text: string
     *   - legal_context: array (релевантные фрагменты НПА)
     * @return array
     */
    public function analyzeDocument(array $payload): array;
    public function generateDocumentContent(string $prompt): array;
}
