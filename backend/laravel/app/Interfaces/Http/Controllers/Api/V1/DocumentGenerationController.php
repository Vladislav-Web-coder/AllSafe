<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Generation\Commands\StartDocumentGenerationCommand;
use App\Application\Generation\UseCases\StartDocumentGenerationUseCase;
use App\Domain\Generation\Repositories\DocumentTemplateRepositoryInterface;
use App\Domain\Generation\Repositories\GenerationRunRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Generation\StartDocumentGenerationRequest;
use App\Interfaces\Http\Resources\Generation\DocumentTemplateResource;
use App\Interfaces\Http\Resources\Generation\GenerationRunResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentGenerationController extends Controller
{
    public function __construct(
        private DocumentTemplateRepositoryInterface $templates,
        private GenerationRunRepositoryInterface $runs,
        private StartDocumentGenerationUseCase $startGeneration,
    ) {}

    public function templates(Request $request, int $organizationId)
    {
        $templates = $this->templates->getActive();

        return DocumentTemplateResource::collection($templates);
    }

    public function store(StartDocumentGenerationRequest $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new StartDocumentGenerationCommand(
            organizationId: $organization->id,
            documentTemplateId: (int) $request->validated('document_template_id'),
            userId: $request->user()->id,
        );

        $run = $this->startGeneration->handle($command);

        return response()->json(new GenerationRunResource($run), 202);
    }

    public function index(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $runs = $this->runs->listForOrganization($organization->id);

        return GenerationRunResource::collection($runs);
    }

    public function show(Request $request, int $organizationId, int $generationRunId)
    {
        $run = $this->runs->findById($generationRunId);

        if (! $run) {
            abort(404, 'Запуск генерации не найден.');
        }

        return new GenerationRunResource($run);
    }
}
