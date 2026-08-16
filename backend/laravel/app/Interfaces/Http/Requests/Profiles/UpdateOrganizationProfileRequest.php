<?php

namespace App\Interfaces\Http\Requests\Profiles;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'processes_personal_data' => ['required', 'boolean'],
            'has_website' => ['required', 'boolean'],
            'has_gis' => ['required', 'boolean'],
            'has_kii' => ['required', 'boolean'],
            'has_asutp' => ['required', 'boolean'],
            'uses_cloud' => ['required', 'boolean'],
            'has_contractors' => ['required', 'boolean'],
            'has_cross_border_transfer' => ['required', 'boolean'],

            'data_categories' => ['nullable', 'array'],
            'data_categories.*' => ['string', 'max:100'],

            'special_data_categories' => ['nullable', 'array'],
            'special_data_categories.*' => ['string', 'max:100'],

            'subjects_count' => ['nullable', 'integer', 'min:0'],

            'protection_level' => ['nullable', 'string', 'in:УЗ-1,УЗ-2,УЗ-3,УЗ-4'],

            'extra_attributes' => ['nullable', 'array'],
        ];
    }
}
