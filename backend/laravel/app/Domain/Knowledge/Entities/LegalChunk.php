<?php

namespace App\Domain\Knowledge\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalChunk extends Model
{
    protected $connection = 'pgsql_knowledge';

    protected $table = 'legal_chunks';

    protected $fillable = [
        'legal_source_id',
        'chunk_index',
        'article',
        'part',
        'clause',
        'title',
        'content',
        'metadata_json',
        'actual_as_of',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'actual_as_of' => 'date',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LegalSource::class, 'legal_source_id');
    }
}
