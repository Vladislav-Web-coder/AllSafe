<?php

namespace App\Domain\Tasks\Entities;

use App\Domain\Analysis\Entities\DocumentIssue;
use App\Domain\Documents\Entities\Document;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskSourceType;
use App\Domain\Tasks\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql_core';

    protected $table = 'tasks';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'status',
        'priority',
        'source_type',
        'document_issue_id',
        'document_id',
        'assigned_to',
        'created_by',
        'due_date',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'source_type' => TaskSourceType::class,
            'due_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(DocumentIssue::class, 'document_issue_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)
            ->orderBy('created_at');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return $this->isActive() && $this->due_date->isPast();
    }

    public function start(): void
    {
        $this->update([
            'status' => TaskStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }
}
