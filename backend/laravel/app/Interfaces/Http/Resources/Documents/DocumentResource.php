<?php

namespace App\Interfaces\Http\Resources\Documents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'source' => $this->source,
            'source_label' => $this->source?->label(),

            'document_type' => [
                'id' => $this->type?->id,
                'code' => $this->type?->code,
                'name' => $this->type?->name,
                'category' => $this->type?->category,
            ],

            'current_version' => $this->whenLoaded(
                'currentVersion',
                fn () => $this->currentVersion ? [
                    'id' => $this->currentVersion->id,
                    'version_number' => $this->currentVersion->version_number,
                    'file_name' => $this->currentVersion->file_name,
                    'file_size' => $this->currentVersion->file_size,
                    'mime_type' => $this->currentVersion->mime_type,
                    'created_at' => $this->currentVersion->created_at,
                ] : null,
            ),

            'metadata' => $this->metadata_json,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
