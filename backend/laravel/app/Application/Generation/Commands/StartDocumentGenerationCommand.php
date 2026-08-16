<?php

namespace App\Application\Generation\Commands;

class StartDocumentGenerationCommand
{
    public function __construct(
        public readonly int $organizationId,
        public readonly int $documentTemplateId,
        public readonly int $userId,
    ) {}
}
