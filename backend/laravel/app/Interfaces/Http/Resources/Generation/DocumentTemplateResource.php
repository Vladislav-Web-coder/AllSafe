<?php

namespace App\Interfaces\Http\Resources\Generation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'required_sections' => $this->required_sections_json,

            'document_type' => [
                'id' => $this->documentType?->id,
                'code' => $this->documentType?->code,
                'name' => $this->documentType?->name,
                'category' => $this->documentType?->category,
            ],
        ];
    }
}
