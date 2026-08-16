<?php

namespace App\Application\Tasks\UseCases;

use App\Application\Tasks\Commands\CreateTaskFromIssueCommand;
use App\Domain\Analysis\Enums\IssueSeverity;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Tasks\Entities\Task;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskSourceType;
use Illuminate\Validation\ValidationException;

class CreateTaskFromIssueUseCase
{
    public function __construct(
        private DocumentIssueRepositoryInterface $issues,
        private CreateTaskUseCase $createTask,
    ) {}

    public function handle(CreateTaskFromIssueCommand $command): Task
    {
        $issue = $this->issues->findById($command->issueId);

        if (! $issue) {
            throw ValidationException::withMessages([
                'issue_id' => ['Замечание не найдено.'],
            ]);
        }

        // Проверяем, что замечание принадлежит организации
        if ($issue->organization_id !== $command->organizationId) {
            throw ValidationException::withMessages([
                'issue_id' => ['Замечание не принадлежит данной организации.'],
            ]);
        }

        // Определяем приоритет задачи на основе критичности замечания
        $priority = $this->mapSeverityToPriority($issue->severity);

        // Формируем заголовок и описание
        $title = "Исправить замечание: {$issue->title}";

        $description = $issue->recommendation ?? $issue->description ?? '';

        if (! empty($issue->legal_basis_json)) {
            $description .= "\n\nНормативное основание: " . implode(', ', $issue->legal_basis_json);
        }

        return $this->createTask->handle(
            new \App\Application\Tasks\Commands\CreateTaskCommand(
                organizationId: $command->organizationId,
                title: $title,
                description: $description,
                priority: $priority,
                sourceType: TaskSourceType::Issue,
                documentIssueId: $issue->id,
                documentId: $issue->document_id,
                assignedTo: $command->assignedTo,
                dueDate: $command->dueDate,
                createdBy: $command->userId,
            )
        );
    }

    private function mapSeverityToPriority(IssueSeverity $severity): TaskPriority
    {
        return match ($severity) {
            IssueSeverity::Critical => TaskPriority::Critical,
            IssueSeverity::High => TaskPriority::High,
            IssueSeverity::Medium => TaskPriority::Medium,
            IssueSeverity::Low, IssueSeverity::Info => TaskPriority::Low,
        };
    }
}
