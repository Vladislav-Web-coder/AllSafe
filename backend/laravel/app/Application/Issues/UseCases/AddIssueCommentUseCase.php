<?php

namespace App\Application\Issues\UseCases;

use App\Application\Issues\Commands\AddIssueCommentCommand;
use App\Domain\Analysis\Repositories\DocumentIssueRepositoryInterface;
use App\Domain\Issues\Entities\IssueComment;
use App\Domain\Issues\Repositories\IssueCommentRepositoryInterface;
use App\Domain\Issues\Repositories\IssueHistoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddIssueCommentUseCase
{
    public function __construct(
        private DocumentIssueRepositoryInterface $issues,
        private IssueCommentRepositoryInterface $comments,
        private IssueHistoryRepositoryInterface $history,
    ) {}

    public function handle(AddIssueCommentCommand $command): IssueComment
    {
        $issue = $this->issues->findById($command->issueId);

        if (! $issue) {
            throw ValidationException::withMessages([
                'issue_id' => ['Замечание не найдено.'],
            ]);
        }

        return DB::connection('pgsql_core')->transaction(function () use ($command, $issue) {
            $comment = $this->comments->create([
                'document_issue_id' => $issue->id,
                'user_id' => $command->userId,
                'content' => $command->content,
            ]);

            $this->history->create([
                'document_issue_id' => $issue->id,
                'user_id' => $command->userId,
                'change_type' => 'comment_added',
                'comment' => $command->content,
            ]);

            return $comment;
        });
    }
}
