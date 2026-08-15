<?php

namespace App\Domain\Documents\Entities;

use App\Domain\Documents\Enums\DocumentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $connection = 'pgsql_core';

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'version_number',
        'source',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'file_hash',
        'storage_disk',
        'parsed_text_path',
        'created_by',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'source' => DocumentSource::class,
            'metadata_json' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
