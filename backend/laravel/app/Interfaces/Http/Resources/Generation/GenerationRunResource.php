<?php

namespace App\Interfaces\Http\Resources\Generation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'document_id' => $this->document_id,
            'error_message' => $this->error_message,

            'template' => [
                'id' => $this->template?->id,
                'code' => $this->template?->code,
                'name' => $this->template?->name,
            ],

            'document_type' => [
                'id' => $this->template?->documentType?->id,
                'code' => $this->template?->documentType?->code,
                'name' => $this->template?->documentType?->name,
            ],

            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
        ];
    }
}
