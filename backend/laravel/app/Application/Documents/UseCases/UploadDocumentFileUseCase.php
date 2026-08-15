<?php

namespace App\Application\Documents\UseCases;

use App\Application\Documents\Commands\UploadDocumentFileCommand;
use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Entities\DocumentVersion;
use App\Domain\Documents\Enums\DocumentSource;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentVersionRepositoryInterface;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadDocumentFileUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documents,
        private DocumentVersionRepositoryInterface $versions,
        private DocumentFileStorageInterface $fileStorage,
    ) {}

    public function handle(UploadDocumentFileCommand $command): DocumentVersion
    {
        return DB::connection('pgsql_core')->transaction(function () use ($command) {
            $document = $command->document;

            $versionUuid = (string) Str::uuid();

            $storedFile = $this->fileStorage->storeVersionFile(
                document: $document,
                file: $command->file,
                versionUuid: $versionUuid,
            );

            $versionNumber = $this->versions->nextVersionNumber($document->id);

            $version = $this->versions->create([
                'document_id' => $document->id,
                'version_number' => $versionNumber,
                'source' => DocumentSource::Upload,
                'file_path' => $storedFile->path,
                'file_name' => $storedFile->originalName,
                'file_size' => $storedFile->size,
                'mime_type' => $storedFile->mimeType,
                'file_hash' => $storedFile->hash,
                'storage_disk' => $storedFile->disk,
                'created_by' => $command->userId,
                'metadata_json' => [
                    'comment' => $command->comment,
                ],
            ]);

            $this->documents->update($document, [
                'status' => DocumentStatus::Uploaded,
                'source' => DocumentSource::Upload,
                'current_version_id' => $version->id,
                'updated_by' => $command->userId,
            ]);

            return $version;
        });
    }
}
