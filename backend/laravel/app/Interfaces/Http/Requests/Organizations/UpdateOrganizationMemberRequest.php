<?php

namespace App\Interfaces\Http\Requests\Organizations;

use App\Domain\Organizations\Enums\OrganizationRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in(array_column(OrganizationRole::cases(), 'value')),
            ],
        ];
    }
}
