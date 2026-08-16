<?php

namespace App\Interfaces\Http\Resources\Profiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,

            'processes_personal_data' => $this->processes_personal_data,
            'has_website' => $this->has_website,
            'has_gis' => $this->has_gis,
            'has_kii' => $this->has_kii,
            'has_asutp' => $this->has_asutp,
            'uses_cloud' => $this->uses_cloud,
            'has_contractors' => $this->has_contractors,
            'has_cross_border_transfer' => $this->has_cross_border_transfer,

            'data_categories' => $this->data_categories,
            'special_data_categories' => $this->special_data_categories,
            'subjects_count' => $this->subjects_count,
            'protection_level' => $this->protection_level,
            'extra_attributes' => $this->extra_attributes,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
