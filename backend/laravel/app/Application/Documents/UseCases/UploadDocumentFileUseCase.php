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
use Illuminate\Support\Facades\Log;
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

            Log::info('UploadDocumentFileUseCase: start', [
                'document_id' => $document->id,
                'file_name' => $command->file->getClientOriginalName(),
                'user_id' => $command->userId,
            ]);

            $versionUuid = (string) Str::uuid();

            // Загружаем файл в MinIO
            $storedFile = $this->fileStorage->storeVersionFile(
                document: $document,
                file: $command->file,
                versionUuid: $versionUuid,
            );

            Log::info('UploadDocumentFileUseCase: file stored', [
                'document_id' => $document->id,
                'file_path' => $storedFile->path,
                'file_size' => $storedFile->size,
            ]);

            // Определяем номер версии
            $versionNumber = $this->versions->nextVersionNumber($document->id);

            // Создаём версию
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

            Log::info('UploadDocumentFileUseCase: version created', [
                'version_id' => $version->id,
                'document_id' => $document->id,
                'version_number' => $version->version_number,
            ]);

            // Обновляем документ
            $this->documents->update($document, [
                'status' => DocumentStatus::Uploaded,
                'source' => DocumentSource::Upload,
                'current_version_id' => $version->id,
                'updated_by' => $command->userId,
            ]);

            Log::info('UploadDocumentFileUseCase: document updated', [
                'document_id' => $document->id,
                'current_version_id' => $version->id,
                'status' => DocumentStatus::Uploaded->value,
            ]);

            return $version;
        });
    }
}
