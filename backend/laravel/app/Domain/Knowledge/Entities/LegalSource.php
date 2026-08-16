<?php

namespace App\Domain\Knowledge\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalSource extends Model
{
    protected $connection = 'pgsql_knowledge';

    protected $table = 'legal_sources';

    protected $fillable = [
        'source_type',
        'title',
        'number',
        'published_at',
        'actual_as_of',
        'source_url',
        'is_active',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'actual_as_of' => 'date',
            'is_active' => 'boolean',
            'metadata_json' => 'array',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(LegalChunk::class);
    }
}
