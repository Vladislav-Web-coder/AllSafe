<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Documents\Commands\CreateDocumentCommand;
use App\Application\Documents\Commands\UploadDocumentFileCommand;
use App\Application\Documents\UseCases\CreateDocumentUseCase;
use App\Application\Documents\UseCases\UploadDocumentFileUseCase;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Documents\Entities\Document;
use App\Domain\Documents\Entities\DocumentVersion;
use App\Domain\Documents\Enums\DocumentSource;
use App\Domain\Documents\Repositories\DocumentRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Storage\DocumentFileStorageInterface;
use App\Interfaces\Http\Requests\Documents\StoreDocumentRequest;
use App\Interfaces\Http\Requests\Documents\UploadDocumentFileRequest;
use App\Interfaces\Http\Resources\Documents\DocumentResource;
use App\Interfaces\Http\Resources\Documents\DocumentVersionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentRepositoryInterface $documents,
        private CreateDocumentUseCase $createDocument,
        private UploadDocumentFileUseCase $uploadDocumentFile,
        private DocumentFileStorageInterface $fileStorage,
    ) {}

    public function index(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $documents = $this->documents->listForOrganization($organization->id);

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new CreateDocumentCommand(
            organizationId: $organization->id,
            userId: $request->user()->id,
            documentTypeId: (int) $request->validated('document_type_id'),
            title: $request->validated('title'),
            source: DocumentSource::Manual,
            metadata: $request->validated('metadata'),
        );

        $document = $this->createDocument->handle($command);

        return response()->json(new DocumentResource($document), 201);
    }

    public function show(Request $request, int $organizationId, int $documentId)
    {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $document->load(['type', 'currentVersion']);

        return new DocumentResource($document);
    }

    public function upload(
        UploadDocumentFileRequest $request,
        int $organizationId,
        int $documentId,
    ): JsonResponse {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $command = new UploadDocumentFileCommand(
            document: $document,
            file: $request->file('file'),
            userId: $request->user()->id,
            comment: $request->validated('comment'),
        );

        $version = $this->uploadDocumentFile->handle($command);

        $this->audit->logFromRequest(
            action: AuditAction::DocumentFileUploaded,
            request: $request,
            subjectType: 'document',
            subjectId: $document->id,
            description: "Загружен файл: {$version->file_name}, версия {$version->version_number}",
            newValues: [
                'version_number' => $version->version_number,
                'file_name' => $version->file_name,
                'file_size' => $version->file_size,
            ],
        );

        return response()->json(new DocumentVersionResource($version), 201);
    }

    public function download(Request $request, int $organizationId, int $documentId): JsonResponse
    {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $document->load('currentVersion');

        if (! $document->currentVersion) {
            return response()->json([
                'message' => 'У документа ещё нет загруженной версии.',
            ], 409);
        }

        $url = $this->fileStorage->temporaryUrl(
            $document->currentVersion->file_path,
            300,
        );

        return response()->json([
            'download_url' => $url,
            'expires_in' => 300,
            'file_name' => $document->currentVersion->file_name,
        ]);
    }
}
