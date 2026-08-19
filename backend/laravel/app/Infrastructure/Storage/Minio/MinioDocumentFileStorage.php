<?php

namespace App\Infrastructure\Storage\Minio;

use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\ValueObjects\StoredFile;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MinioDocumentFileStorage implements DocumentFileStorageInterface
{
    public function storeVersionFile(
        Document $document,
        UploadedFile $file,
        string $versionUuid,
    ): StoredFile {
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
        $presignEndpoint = config('filesystems.disks.minio.presign_endpoint');

        if (! $presignEndpoint) {
            // Fallback: используем стандартный метод
            return Storage::disk('minio')->temporaryUrl(
                $path,
                now()->addSeconds($seconds),
            );
        }

        // Создаём S3 клиент с внешним endpoint для генерации presigned URL
        $client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.minio.region', 'us-east-1'),
            'endpoint' => $presignEndpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => config('filesystems.disks.minio.key'),
                'secret' => config('filesystems.disks.minio.secret'),
            ],
        ]);

        $bucket = config('filesystems.disks.minio.bucket');

        $command = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $request = $client->createPresignedRequest($command, "+{$seconds} seconds");

        return (string) $request->getUri();
    }
}
