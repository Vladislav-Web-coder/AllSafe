<?php

namespace App\Infrastructure\Storage\Minio;

use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\ValueObjects\StoredFile;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioDocumentFileStorage implements DocumentFileStorageInterface
{
    public function storeVersionFile(
        Document $document,
        UploadedFile $file,
        string $versionUuid,
    ): StoredFile {
        $disk = Storage::disk('minio');

        $directory = sprintf(
            'organizations/%d/documents/%d/versions/%s',
            $document->organization_id,
            $document->id,
            $versionUuid,
        );

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');

        $fileName = "original.{$extension}";

        $path = $file->storeAs($directory, $fileName, 'minio');

        $hash = hash_file('sha256', $file->getRealPath());

        return new StoredFile(
            path: $path,
            originalName: $file->getClientOriginalName(),
            size: (int) $file->getSize(),
            mimeType: $file->getMimeType(),
            hash: $hash,
            disk: 'minio',
        );
    }

    public function temporaryUrl(string $path, int $seconds = 300): string
    {
        return Storage::disk('minio')->temporaryUrl(
            $path,
            now()->addSeconds($seconds),
        );
    }
}
