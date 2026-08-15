<?php

namespace App\Application\Documents\UseCases;

use App\Application\Documents\Commands\CreateDocumentCommand;
use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Domain\Documents\Repositories\DocumentTypeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDocumentUseCase
{
    public function __construct(
        private DocumentRepositoryInterface $documents,
        private DocumentTypeRepositoryInterface $documentTypes,
    ) {}

    public function handle(CreateDocumentCommand $command): Document
    {
        $documentType = $this->documentTypes->findById($command->documentTypeId);

        if (! $documentType || ! $documentType->is_active) {
            throw ValidationException::withMessages([
                'document_type_id' => ['Тип документа не найден или неактивен.'],
            ]);
        }

        return DB::connection('pgsql_core')->transaction(function () use ($command) {
            return $this->documents->create([
                'organization_id' => $command->organizationId,
                'document_type_id' => $command->documentTypeId,
                'title' => $command->title,
                'status' => DocumentStatus::Draft,
                'source' => $command->source,
                'created_by' => $command->userId,
                'updated_by' => $command->userId,
                'metadata_json' => $command->metadata,
            ]);
        });
    }
}
