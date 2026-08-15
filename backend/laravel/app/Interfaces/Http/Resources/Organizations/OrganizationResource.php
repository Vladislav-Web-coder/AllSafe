<?php

namespace App\Interfaces\Http\Resources\Organizations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentMembership = $this->memberships->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'inn' => $this->inn,

            'organization_type' => [
                'id' => $this->type?->id,
                'code' => $this->type?->code,
                'name' => $this->type?->name,
            ],

            'industry' => [
                'id' => $this->industry?->id,
                'code' => $this->industry?->code,
                'name' => $this->industry?->name,
            ],

            'status' => $this->status,
            'role' => $currentMembership?->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
