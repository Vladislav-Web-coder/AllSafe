<?php

namespace App\Interfaces\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'analysis_run_id' => $this->analysis_run_id,
            'document_id' => $this->document_id,

            'requirement_code' => $this->requirement_code,

            'severity' => $this->severity,
            'severity_label' => $this->severity?->label(),

            'title' => $this->title,
            'description' => $this->description,
            'recommendation' => $this->recommendation,

            'legal_basis' => $this->legal_basis_json,
            'section_code' => $this->section_code,

            'status' => $this->status,
            'status_label' => $this->status?->label(),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
