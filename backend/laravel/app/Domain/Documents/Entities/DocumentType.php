<?php

namespace App\Domain\Documents\Entities;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
        'can_be_generated',
        'required_sections_json',
        'legal_basis_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'can_be_generated' => 'boolean',
            'required_sections_json' => 'array',
            'legal_basis_json' => 'array',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
