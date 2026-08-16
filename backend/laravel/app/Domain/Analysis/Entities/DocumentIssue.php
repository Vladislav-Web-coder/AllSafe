<?php

namespace App\Domain\Analysis\Entities;

use App\Domain\Analysis\Enums\IssueSeverity;
use App\Domain\Analysis\Enums\IssueStatus;
use App\Domain\Documents\Entities\Document;
use App\Domain\Issues\Entities\IssueComment;
use App\Domain\Issues\Entities\IssueHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentIssue extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_issues';

    protected $fillable = [
        'analysis_run_id',
        'document_id',
        'document_version_id',
        'organization_id',
        'requirement_code',
        'severity',
        'title',
        'description',
        'recommendation',
        'legal_basis_json',
        'section_code',
        'status',
        'user_comment',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => IssueSeverity::class,
            'status' => IssueStatus::class,
            'legal_basis_json' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class, 'analysis_run_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class)
            ->orderBy('created_at');
    }

    public function history(): HasMany
    {
        return $this->hasMany(IssueHistory::class)
            ->orderBy('created_at', 'desc');
    }

    public function isOpen(): bool
    {
        return $this->status === IssueStatus::Open;
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [
            IssueStatus::Fixed,
            IssueStatus::Rejected,
        ]);
    }

    public function canTransitionTo(IssueStatus $newStatus): bool
    {
        return IssueStatusTransition::canTransition($this->status, $newStatus);
    }
}
