<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Issues\Entities\IssueComment;
use App\Domain\Issues\Repositories\IssueCommentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentIssueCommentRepository implements IssueCommentRepositoryInterface
{
    public function create(array $data): IssueComment
    {
        return IssueComment::query()->create($data);
    }

    public function listForIssue(int $issueId): Collection
    {
        return IssueComment::query()
            ->where('document_issue_id', $issueId)
            ->orderBy('created_at')
            ->get();
    }

    public function findById(int $id): ?IssueComment
    {
        return IssueComment::query()->find($id);
    }

    public function delete(IssueComment $comment): void
    {
        $comment->delete();
    }
}
