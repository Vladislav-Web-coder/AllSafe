<?php

namespace App\Infrastructure\Storage;

use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\ValueObjects\StoredFile;
use Illuminate\Http\UploadedFile;

interface DocumentFileStorageInterface
{
    public function storeVersionFile(
        Document $document,
        UploadedFile $file,
        string $versionUuid,
    ): StoredFile;

    public function temporaryUrl(string $path, int $seconds = 300): string;
}
