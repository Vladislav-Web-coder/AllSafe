<?php

namespace App\Interfaces\Http\Resources\Documents;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'version_number' => $this->version_number,
            'source' => $this->source,
            'source_label' => $this->source?->label(),
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'file_hash' => $this->file_hash,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
