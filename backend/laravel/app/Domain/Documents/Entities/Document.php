<?php

namespace App\Domain\Documents\Entities;

use App\Domain\Documents\Enums\DocumentSource;
use App\Domain\Documents\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql_core';

    protected $table = 'documents';

    protected $fillable = [
        'organization_id',
        'document_type_id',
        'title',
        'status',
        'source',
        'current_version_id',
        'created_by',
        'updated_by',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'source' => DocumentSource::class,
            'metadata_json' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function isDraft(): bool
    {
        return $this->status === DocumentStatus::Draft;
    }

    public function isArchived(): bool
    {
        return $this->status === DocumentStatus::Archived;
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return (int) $this->organization_id === $organizationId;
    }
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }
}
