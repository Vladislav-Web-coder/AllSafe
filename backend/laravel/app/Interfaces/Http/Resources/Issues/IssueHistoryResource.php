<?php

namespace App\Interfaces\Http\Resources\Issues;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_issue_id' => $this->document_issue_id,
            'user_id' => $this->user_id,
            'change_type' => $this->change_type,
            'field_changed' => $this->field_changed,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
