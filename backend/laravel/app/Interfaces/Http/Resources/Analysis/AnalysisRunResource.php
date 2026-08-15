<?php

namespace App\Interfaces\Http\Resources\Analysis;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'document_version_id' => $this->document_version_id,
            'organization_id' => $this->organization_id,

            'status' => $this->status,
            'status_label' => $this->status?->label(),

            'score' => $this->score,

            'summary' => $this->summary_json,
            'missing_sections' => $this->missing_sections_json,
            'legal_references' => $this->legal_references_json,

            'model_provider' => $this->model_provider,
            'model_name' => $this->model_name,

            'error_message' => $this->error_message,

            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
