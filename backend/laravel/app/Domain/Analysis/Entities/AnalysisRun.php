<?php

namespace App\Domain\Analysis\Entities;

use App\Domain\Analysis\Enums\AnalysisStatus;
use App\Domain\Documents\Entities\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisRun extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_analysis_runs';

    protected $fillable = [
        'document_id',
        'document_version_id',
        'organization_id',
        'status',
        'score',
        'summary_json',
        'missing_sections_json',
        'legal_references_json',
        'model_provider',
        'model_name',
        'prompt_version',
        'knowledge_base_version',
        'requirements_version',
        'started_at',
        'finished_at',
        'error_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
            'summary_json' => 'array',
            'missing_sections_json' => 'array',
            'legal_references_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(
            DocumentIssue::class,
            'analysis_run_id'
        );
    }
}
