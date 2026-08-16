<?php

namespace App\Domain\Generation\Entities;

use App\Domain\Generation\Enums\GenerationStatus;
use Illuminate\Database\Eloquent\Model;

class GenerationRun extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'generation_runs';

    protected $fillable = [
        'organization_id',
        'document_template_id',
        'status',
        'document_id',
        'error_message',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function template()
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function generatedDocument()
    {
        return $this->hasOne(GeneratedDocument::class);
    }
}
