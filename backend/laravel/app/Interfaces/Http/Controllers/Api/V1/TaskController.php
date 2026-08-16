<?php

namespace App\Interfaces\Http\Controllers\Api\V1;

use App\Application\Tasks\Commands\AssignTaskCommand;
use App\Application\Tasks\Commands\CreateTaskCommand;
use App\Application\Tasks\Commands\CreateTaskFromIssueCommand;
use App\Application\Tasks\Commands\UpdateTaskStatusCommand;
use App\Application\Tasks\UseCases\AssignTaskUseCase;
use App\Application\Tasks\UseCases\CreateTaskFromIssueUseCase;
use App\Application\Tasks\UseCases\CreateTaskUseCase;
use App\Application\Tasks\UseCases\UpdateTaskStatusUseCase;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskSourceType;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Repositories\TaskCommentRepositoryInterface;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Interfaces\Http\Requests\Tasks\AddTaskCommentRequest;
use App\Interfaces\Http\Requests\Tasks\AssignTaskRequest;
use App\Interfaces\Http\Requests\Tasks\CreateTaskFromIssueRequest;
use App\Interfaces\Http\Requests\Tasks\StoreTaskRequest;
use App\Interfaces\Http\Requests\Tasks\UpdateTaskStatusRequest;
use App\Interfaces\Http\Resources\Tasks\TaskCommentResource;
use App\Interfaces\Http\Resources\Tasks\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
        private TaskCommentRepositoryInterface $taskComments,
        private CreateTaskUseCase $createTask,
        private CreateTaskFromIssueUseCase $createFromIssue,
        private UpdateTaskStatusUseCase $updateStatus,
        private AssignTaskUseCase $assignTask,
    ) {}

    /**
     * Список задач организации.
     */
    public function index(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $status = $request->query('status');

        if ($status === 'open') {
            $tasks = $this->tasks->listOpenForOrganization($organization->id);
        } elseif ($status === 'overdue') {
            $tasks = $this->tasks->listOverdueForOrganization($organization->id);
        } else {
            $tasks = $this->tasks->listForOrganization($organization->id);
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Статистика по задачам.
     */
    public function stats(Request $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $counts = $this->tasks->countByStatus($organization->id);

        return response()->json([
            'data' => $counts,
        ]);
    }

    /**
     * Задачи текущего пользователя.
     */
    public function myTasks(Request $request, int $organizationId)
    {
        $organization = $request->attributes->get('currentOrganization');

        $tasks = $this->tasks->listForUser($organization->id, $request->user()->id);

        return TaskResource::collection($tasks);
    }

    /**
     * Создать задачу вручную.
     */
    public function store(StoreTaskRequest $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new CreateTaskCommand(
            organizationId: $organization->id,
            title: $request->validated('title'),
            description: $request->validated('description'),
            priority: TaskPriority::from($request->validated('priority', 'medium')),
            sourceType: TaskSourceType::Manual,
            documentIssueId: $request->validated('document_issue_id'),
            documentId: $request->validated('document_id'),
            assignedTo: $request->validated('assigned_to'),
            dueDate: $request->validated('due_date'),
            createdBy: $request->user()->id,
        );

        $task = $this->createTask->handle($command);

        return response()->json(new TaskResource($task), 201);
    }

    /**
     * Создать задачу из замечания.
     */
    public function createFromIssue(CreateTaskFromIssueRequest $request, int $organizationId): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $command = new CreateTaskFromIssueCommand(
            issueId: (int) $request->validated('issue_id'),
            organizationId: $organization->id,
            userId: $request->user()->id,
            assignedTo: $request->validated('assigned_to'),
            dueDate: $request->validated('due_date'),
        );

        $task = $this->createFromIssue->handle($command);

        return response()->json(new TaskResource($task), 201);
    }

    /**
     * Конкретная задача.
     */
    public function show(Request $request, int $organizationId, int $taskId)
    {
        $task = $this->tasks->findById($taskId);

        if (! $task) {
            abort(404, 'Задача не найдена.');
        }

        return new TaskResource($task);
    }

    /**
     * Обновить статус задачи.
     */
    public function updateStatus(
        UpdateTaskStatusRequest $request,
        int $organizationId,
        int $taskId,
    ): JsonResponse {
        $command = new UpdateTaskStatusCommand(
            taskId: $taskId,
            newStatus: TaskStatus::from($request->validated('status')),
            userId: $request->user()->id,
        );

        $task = $this->updateStatus->handle($command);

        return response()->json(new TaskResource($task));
    }

    /**
     * Назначить ответственного.
     */
    public function assign(
        AssignTaskRequest $request,
        int $organizationId,
        int $taskId,
    ): JsonResponse {
        $command = new AssignTaskCommand(
            taskId: $taskId,
            assignedTo: (int) $request->validated('assigned_to'),
            userId: $request->user()->id,
        );

        $task = $this->assignTask->handle($command);

        return response()->json(new TaskResource($task));
    }

    /**
     * Удалить задачу.
     */
    public function destroy(Request $request, int $organizationId, int $taskId): JsonResponse
    {
        $task = $this->tasks->findById($taskId);

        if (! $task) {
            abort(404, 'Задача не найдена.');
        }

        $this->tasks->delete($task);

        return response()->json(null, 204);
    }

    /**
     * Добавить комментарий к задаче.
     */
    public function addComment(
        AddTaskCommentRequest $request,
        int $organizationId,
        int $taskId,
    ): JsonResponse {
        $comment = $this->taskComments->create([
            'task_id' => $taskId,
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return response()->json(new TaskCommentResource($comment), 201);
    }

    /**
     * Список комментариев задачи.
     */
    public function listComments(Request $request, int $organizationId, int $taskId)
    {
        $comments = $this->taskComments->listForTask($taskId);

        return TaskCommentResource::collection($comments);
    }
}
