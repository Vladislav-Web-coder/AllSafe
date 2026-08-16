<?php

namespace App\Interfaces\Http\Resources\Analysis;

use App\Domain\Issues\Services\IssueStatusTransition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allowedTransitions = IssueStatusTransition::getAllowedTransitions($this->status);

        return [
            'id' => $this->id,
            'analysis_run_id' => $this->analysis_run_id,
            'document_id' => $this->document_id,
            'organization_id' => $this->organization_id,

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

            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $allowedTransitions
            ),

            'user_comment' => $this->user_comment,
            'resolved_by' => $this->resolved_by,
            'resolved_at' => $this->resolved_at,

            'comments_count' => $this->when(
                $this->comments !== null,
                fn () => $this->comments->count()
            ),

            'comments' => $this->whenLoaded('comments'),
            'history' => $this->whenLoaded('history'),

            'document' => $this->whenLoaded('document', fn () => [
                'id' => $this->document->id,
                'title' => $this->document->title,
                'status' => $this->document->status,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
