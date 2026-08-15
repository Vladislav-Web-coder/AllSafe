<?php

namespace App\Application\Documents\Commands;

use App\Domain\Documents\Entities\Document;
use Illuminate\Http\UploadedFile;

class UploadDocumentFileCommand
{
    public function __construct(
        public readonly Document $document,
        public readonly UploadedFile $file,
        public readonly int $userId,
        public readonly ?string $comment = null,
    ) {}
}
