<?php

namespace App\Application\Tasks\UseCases;

use App\Application\Tasks\Commands\CreateTaskCommand;
use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Repositories\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $tasks,
    ) {}

    public function handle(CreateTaskCommand $command): Task
    {
        return DB::connection('pgsql_core')->transaction(function () use ($command) {
            return $this->tasks->create([
                'organization_id' => $command->organizationId,
                'title' => $command->title,
                'description' => $command->description,
                'status' => TaskStatus::New,
                'priority' => $command->priority,
                'source_type' => $command->sourceType,
                'document_issue_id' => $command->documentIssueId,
                'document_id' => $command->documentId,
                'assigned_to' => $command->assignedTo,
                'created_by' => $command->createdBy,
                'due_date' => $command->dueDate,
            ]);
        });
    }
}
