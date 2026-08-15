<?php

namespace App\Application\Documents\Commands;

use App\Domain\Documents\Enums\DocumentSource;

class CreateDocumentCommand
{
    public function __construct(
        public readonly int $organizationId,
        public readonly int $userId,
        public readonly int $documentTypeId,
        public readonly string $title,
        public readonly DocumentSource $source = DocumentSource::Manual,
        public readonly ?array $metadata = null,
    ) {}
}
