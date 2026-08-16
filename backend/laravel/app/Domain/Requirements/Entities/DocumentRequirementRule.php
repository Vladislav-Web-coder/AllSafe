<?php

namespace App\Domain\Requirements\Entities;

use App\Domain\Documents\Entities\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequirementRule extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_requirement_rules';

    protected $fillable = [
        'code',
        'document_type_id',
        'condition_json',
        'priority',
        'obligation_level',
        'legal_basis_json',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'condition_json' => 'array',
            'legal_basis_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function isRequired(): bool
    {
        return $this->obligation_level === 'required';
    }

    public function isRecommended(): bool
    {
        return $this->obligation_level === 'recommended';
    }
}
