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
        'chapter',
        'article',
        'part',
        'clause',
        'title',
        'content',
        'path',
        'metadata_json',
        'actual_as_of',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'actual_as_of' => 'date',
            // embedding НЕ кастуем — это pgvector тип
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LegalSource::class, 'legal_source_id');
    }

    /**
     * Проверяет, есть ли embedding у чанка.
     */
    public function hasEmbedding(): bool
    {
        return $this->embedding !== null;
    }

    /**
     * Человеко-читаемая ссылка: "152-ФЗ, Глава 1, ст. 5, ч. 1"
     */
    public function getReference(): string
    {
        $parts = [];

        if ($this->source) {
            $parts[] = $this->source->number;
        }
        if ($this->chapter) {
            $parts[] = "Глава {$this->chapter}";
        }
        if ($this->article) {
            $parts[] = "ст. {$this->article}";
        }
        if ($this->part) {
            $parts[] = "ч. {$this->part}";
        }
        if ($this->clause) {
            $parts[] = "п. {$this->clause}";
        }

        return implode(', ', $parts) ?: 'общие положения';
    }
}
