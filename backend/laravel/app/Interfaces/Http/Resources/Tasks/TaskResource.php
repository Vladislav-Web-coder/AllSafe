<?php

namespace App\Interfaces\Http\Resources\Tasks;

use App\Domain\Tasks\Services\TaskStatusTransition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $allowedTransitions = TaskStatusTransition::getAllowedTransitions($this->status);

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,

            'title' => $this->title,
            'description' => $this->description,

            'status' => $this->status,
            'status_label' => $this->status?->label(),

            'priority' => $this->priority,
            'priority_label' => $this->priority?->label(),

            'source_type' => $this->source_type,
            'source_type_label' => $this->source_type?->label(),

            'allowed_transitions' => array_map(
                fn ($status) => $status->value,
                $allowedTransitions
            ),

            'document_issue_id' => $this->document_issue_id,
            'document_id' => $this->document_id,
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,

            'due_date' => $this->due_date,
            'is_overdue' => $this->isOverdue(),

            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,

            'issue' => $this->whenLoaded('issue', fn () => [
                'id' => $this->issue->id,
                'title' => $this->issue->title,
                'severity' => $this->issue->severity,
                'status' => $this->issue->status,
            ]),

            'document' => $this->whenLoaded('document', fn () => [
                'id' => $this->document->id,
                'title' => $this->document->title,
            ]),

            'comments' => $this->whenLoaded('comments'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
