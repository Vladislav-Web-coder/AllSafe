<?php

namespace App\Application\Analysis\Commands;

use App\Domain\Documents\Entities\Document;

class StartDocumentAnalysisCommand
{
    public function __construct(
        public readonly Document $document,
        public readonly int $userId,
    ) {}
}
