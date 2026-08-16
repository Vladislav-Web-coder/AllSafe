<?php

namespace App\Domain\Tasks\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
