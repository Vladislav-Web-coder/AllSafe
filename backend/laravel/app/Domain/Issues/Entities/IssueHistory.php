<?php

namespace App\Domain\Issues\Entities;

use App\Domain\Analysis\Entities\DocumentIssue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueHistory extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'issue_history';

    protected $fillable = [
        'document_issue_id',
        'user_id',
        'change_type',
        'field_changed',
        'old_value',
        'new_value',
        'comment',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(DocumentIssue::class, 'document_issue_id');
    }
}
