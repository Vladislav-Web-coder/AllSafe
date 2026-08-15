<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Analysis\Commands\StartDocumentAnalysisCommand;
use App\Application\Analysis\UseCases\StartDocumentAnalysisUseCase;
use App\Domain\Analysis\Repositories\AnalysisRunRepositoryInterface;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Documents\Entities\Document;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Resources\Analysis\AnalysisRunResource;
use App\Interfaces\Http\Resources\Analysis\DocumentIssueResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentAnalysisController extends Controller
{
    public function __construct(
        private StartDocumentAnalysisUseCase $startAnalysis,
        private AnalysisRunRepositoryInterface $analysisRuns,
        private DocumentIssueRepositoryInterface $issues,
    ) {}

    public function analyze(Request $request, int $organizationId, int $documentId): JsonResponse
    {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $command = new StartDocumentAnalysisCommand(
            document: $document,
            userId: $request->user()->id,
        );

        $analysisRun = $this->startAnalysis->handle($command);

        return response()->json(new AnalysisRunResource($analysisRun), 202);
    }

    public function analysis(Request $request, int $organizationId, int $documentId)
    {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $run = $this->analysisRuns->findLatestForDocument($document->id);

        if (! $run) {
            return response()->json([
                'message' => 'Анализ документа ещё не запускался.',
            ], 404);
        }

        return new AnalysisRunResource($run);
    }

    public function issues(Request $request, int $organizationId, int $documentId)
    {
        $document = $request->attributes->get('currentDocument');

        if (! $document instanceof Document) {
            abort(404, 'Документ не найден.');
        }

        $run = $this->analysisRuns->findLatestForDocument($document->id);

        if (! $run) {
            return response()->json([
                'message' => 'Анализ документа ещё не запускался.',
            ], 404);
        }

        $issues = $this->issues->listForRun($run->id);

        return DocumentIssueResource::collection($issues);
    }
}
