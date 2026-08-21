<?php

namespace App\Domain\Training\Entities;

use Illuminate\Database\Eloquent\Model;

class GoldenExample extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'golden_examples';

    protected $fillable = [
        'category',
        'document_type_code',
        'input_document',
        'organization_profile_json',
        'rag_context_json',
        'expected_output_json',
        'difficulty',
        'annotated_by',
        'annotated_at',
        'is_verified',
        'verified_by',
        'verified_at',
        'quality_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'organization_profile_json' => 'array',
            'rag_context_json' => 'array',
            'expected_output_json' => 'array',
            'annotated_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_verified' => 'boolean',
        ];
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForAnalysis($query)
    {
        return $query->where('category', 'analysis');
    }

    public function scopeForGeneration($query)
    {
        return $query->where('category', 'generation');
    }
}
