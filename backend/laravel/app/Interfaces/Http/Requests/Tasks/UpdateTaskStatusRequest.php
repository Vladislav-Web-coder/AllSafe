<?php

namespace App\Interfaces\Http\Requests\Tasks;

use App\Domain\Tasks\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_column(TaskStatus::cases(), 'value')),
            ],
        ];
    }
}
