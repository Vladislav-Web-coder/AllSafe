<?php

namespace App\Interfaces\Http\Resources\Issues;

use App\Domain\Identity\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = null;
        $userRole = null;

        if ($this->user_id) {
            $user = User::on('pgsql_identity')->find($this->user_id);

            if ($user && $this->issue) {
                $organizationId = $this->issue->organization_id;

                $membership = $user->organizations()
                    ->where('organizations.id', $organizationId)
                    ->first();

                $userRole = $membership?->pivot?->role;
            }
        }

        return [
            'id' => $this->id,
            'document_issue_id' => $this->document_issue_id,
            'user_id' => $this->user_id,

            'user' => $this->when($user !== null, function () use ($user, $userRole) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $userRole,
                ];
            }),

            'content' => $this->content,
            'created_at' => $this->created_at,
        ];
    }
}
