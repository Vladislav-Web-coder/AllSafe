<?php

namespace App\Application\Issues\UseCases;

use App\Application\Issues\Commands\BulkUpdateIssuesCommand;
use App\Application\Issues\Commands\UpdateIssueStatusCommand;
use Illuminate\Support\Collection;

class BulkUpdateIssuesUseCase
{
    public function __construct(
        private UpdateIssueStatusUseCase $updateStatus,
    ) {}

    public function handle(BulkUpdateIssuesCommand $command): Collection
    {
        $results = collect();

        foreach ($command->issueIds as $issueId) {
            try {
                $issue = $this->updateStatus->handle(
                    new UpdateIssueStatusCommand(
                        issueId: (int) $issueId,
                        newStatus: $command->newStatus,
                        userId: $command->userId,
                        comment: $command->comment,
                    )
                );

                $results->push([
                    'issue_id' => $issueId,
                    'success' => true,
                    'status' => $issue->status->value,
                ]);
            } catch (\Throwable $e) {
                $results->push([
                    'issue_id' => $issueId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
