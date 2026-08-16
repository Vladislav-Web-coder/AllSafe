<?php

namespace App\Domain\Issues\Entities;

use App\Domain\Analysis\Entities\DocumentIssue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueComment extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'issue_comments';

    protected $fillable = [
        'document_issue_id',
        'user_id',
        'content',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(DocumentIssue::class, 'document_issue_id');
    }
}
