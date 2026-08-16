<?php

namespace App\Application\Issues\UseCases;

use App\Application\Issues\Commands\UpdateIssueStatusCommand;
use App\Domain\Analysis\Entities\DocumentIssue;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Issues\Repositories\IssueHistoryRepositoryInterface;
use App\Domain\Issues\Services\IssueStatusTransition;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateIssueStatusUseCase
{
    public function __construct(
        private DocumentIssueRepositoryInterface $issues,
        private IssueHistoryRepositoryInterface $history,
    ) {}

    public function handle(UpdateIssueStatusCommand $command): DocumentIssue
    {
        $issue = $this->issues->findById($command->issueId);

        if (! $issue) {
            throw ValidationException::withMessages([
                'issue_id' => ['Замечание не найдено.'],
            ]);
        }

        // Проверяем допустимость перехода
        if (! IssueStatusTransition::canTransition($issue->status, $command->newStatus)) {
            throw ValidationException::withMessages([
                'status' => [
                    "Недопустимый переход статуса из '{$issue->status->label()}' в '{$command->newStatus->label()}'.",
                ],
            ]);
        }

        return DB::connection('pgsql_core')->transaction(function () use ($command, $issue) {
            $oldStatus = $issue->status;

            $updateData = [
                'status' => $command->newStatus,
            ];

            // Если статус меняется на fixed или rejected — фиксируем кто и когда
            if (in_array($command->newStatus, [IssueStatus::Fixed, IssueStatus::Rejected])) {
                $updateData['resolved_by'] = $command->userId;
                $updateData['resolved_at'] = now();
            }

            // Если статус возвращается в open — сбрасываем resolved
            if ($command->newStatus === IssueStatus::Open) {
                $updateData['resolved_by'] = null;
                $updateData['resolved_at'] = null;
            }

            $updatedIssue = $this->issues->update($issue, $updateData);

            // Записываем в историю
            $this->history->create([
                'document_issue_id' => $issue->id,
                'user_id' => $command->userId,
                'change_type' => 'status_changed',
                'field_changed' => 'status',
                'old_value' => $oldStatus->value,
                'new_value' => $command->newStatus->value,
                'comment' => $command->comment,
            ]);

            return $updatedIssue;
        });
    }
}
