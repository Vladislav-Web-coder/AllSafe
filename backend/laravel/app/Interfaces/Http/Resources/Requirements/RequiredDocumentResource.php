<?php

namespace App\Interfaces\Http\Resources\Requirements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequiredDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rule_code' => $this->code,
            'priority' => $this->priority,
            'obligation_level' => $this->obligation_level,
            'description' => $this->description,
            'legal_basis' => $this->legal_basis_json,

            'document_type' => [
                'id' => $this->documentType?->id,
                'code' => $this->documentType?->code,
                'name' => $this->documentType?->name,
                'category' => $this->documentType?->category,
            ],

            'is_present' => $this->is_present ?? false,
        ];
    }
}
