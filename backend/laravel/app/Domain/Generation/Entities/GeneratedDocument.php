<?php

namespace App\Domain\Generation\Entities;

use Illuminate\Database\Eloquent\Model;

class GeneratedDocument extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'generated_documents';

    protected $fillable = [
        'generation_run_id',
        'content',
        'sections_json',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'sections_json' => 'array',
            'metadata_json' => 'array',
        ];
    }

    public function generationRun()
    {
        return $this->belongsTo(GenerationRun::class, 'generation_run_id');
    }
}
