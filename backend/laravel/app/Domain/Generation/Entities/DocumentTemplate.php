<?php

namespace App\Domain\Generation\Entities;

use App\Domain\Documents\Entities\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplate extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_templates';

    protected $fillable = [
        'code',
        'document_type_id',
        'name',
        'description',
        'generation_prompt',
        'required_sections_json',
        'template_variables_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_sections_json' => 'array',
            'template_variables_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
