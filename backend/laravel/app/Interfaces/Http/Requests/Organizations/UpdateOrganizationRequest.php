<?php

namespace App\Interfaces\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:20'],

            'organization_type_id' => [
                'required',
                'integer',
                'exists:pgsql_identity.organization_types,id',
            ],

            'industry_id' => [
                'required',
                'integer',
                'exists:pgsql_identity.industries,id',
            ],
        ];
    }
}
