<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Issues\Commands\AddIssueCommentCommand;
use App\Application\Issues\Commands\BulkUpdateIssuesCommand;
use App\Application\Issues\Commands\UpdateIssueStatusCommand;
use App\Application\Issues\UseCases\AddIssueCommentUseCase;
use App\Application\Issues\UseCases\BulkUpdateIssuesUseCase;
use App\Application\Issues\UseCases\UpdateIssueStatusUseCase;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Issues\Repositories\IssueCommentRepositoryInterface;
use App\Domain\Issues\Repositories\IssueHistoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Issues\AddIssueCommentRequest;
use App\Interfaces\Http\Requests\Issues\BulkUpdateIssuesRequest;
use App\Interfaces\Http\Requests\Issues\UpdateIssueStatusRequest;
use App\Interfaces\Http\Resources\Analysis\DocumentIssueResource;
use App\Interfaces\Http\Resources\Issues\IssueCommentResource;
use App\Interfaces\Http\Resources\Issues\IssueHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function __construct(
        private DocumentIssueRepositoryInterface $issues,
        private IssueCommentRepositoryInterface $comments,
        private IssueHistoryRepositoryInterface $history,
        private UpdateIssueStatusUseCase $updateStatus,
        private AddIssueCommentUseCase $addComment,
        private BulkUpdateIssuesUseCase $bulkUpdate,
    ) {}

    /**
     * Список замечаний организации.
     */
    public function index(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $status = $request->query('status');

        if ($status === 'open') {
            $issues = $this->issues->listOpenForOrganization($organization->id);
        } else {
            $issues = $this->issues->listForOrganization($organization->id);
        }

        return DocumentIssueResource::collection($issues);
    }

    /**
     * Список замечаний конкретного документа.
     */
    public function listForDocument(Request $request, int $organizationId, int $documentId)
    {
        $issues = $this->issues->listForDocument($documentId);

        return DocumentIssueResource::collection($issues);
    }

    /**
     * Конкретное замечание.
     */
    public function show(Request $request, int $organizationId, int $documentId, int $issueId)
    {
        $issue = $this->issues->findById($issueId);

        if (! $issue) {
            abort(404, 'Замечание не найдено.');
        }

        return new DocumentIssueResource($issue);
    }

    /**
     * Обновление статуса замечания.
     */
    public function updateStatus(
        UpdateIssueStatusRequest $request,
        int $organizationId,
        int $documentId,
        int $issueId,
    ): JsonResponse {
        $command = new UpdateIssueStatusCommand(
            issueId: $issueId,
            newStatus: IssueStatus::from($request->validated('status')),
            userId: $request->user()->id,
            comment: $request->validated('comment'),
        );

        $issue = $this->updateStatus->handle($command);

        $this->audit->logFromRequest(
            action: AuditAction::IssueStatusChanged,
            request: $request,
            subjectType: 'issue',
            subjectId: $issue->id,
            description: "Статус замечания изменён на: {$issue->status->label()}",
            oldValues: ['status' => $oldStatus->value ?? null],
            newValues: ['status' => $issue->status->value],
        );

        return response()->json(new DocumentIssueResource($issue));
    }

    /**
     * Добавить комментарий к замечанию.
     */
    public function addComment(
        AddIssueCommentRequest $request,
        int $organizationId,
        int $documentId,
        int $issueId,
    ): JsonResponse {
        $command = new AddIssueCommentCommand(
            issueId: $issueId,
            userId: $request->user()->id,
            content: $request->validated('content'),
        );

        $comment = $this->addComment->handle($command);

        return response()->json(new IssueCommentResource($comment), 201);
    }

    /**
     * Список комментариев к замечанию.
     */
    public function listComments(Request $request, int $organizationId, int $documentId, int $issueId)
    {
        $comments = $this->comments->listForIssue($issueId);

        return IssueCommentResource::collection($comments);
    }

    /**
     * История изменений замечания.
     */
    public function listHistory(Request $request, int $organizationId, int $documentId, int $issueId)
    {
        $history = $this->history->listForIssue($issueId);

        return IssueHistoryResource::collection($history);
    }

    /**
     * Массовое обновление замечаний.
     */
    public function bulkUpdate(BulkUpdateIssuesRequest $request, int $organizationId): JsonResponse
    {
        $command = new BulkUpdateIssuesCommand(
            issueIds: $request->validated('issue_ids'),
            newStatus: IssueStatus::from($request->validated('status')),
            userId: $request->user()->id,
            comment: $request->validated('comment'),
        );

        $results = $this->bulkUpdate->handle($command);

        return response()->json([
            'results' => $results,
            'total' => $results->count(),
            'success_count' => $results->where('success', true)->count(),
            'failed_count' => $results->where('success', false)->count(),
        ]);
    }
}
