<?php

namespace App\Interfaces\Http\Requests\Tasks;

use App\Domain\Tasks\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:1', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],

            'priority' => [
                'sometimes',
                'string',
                Rule::in(array_column(TaskPriority::cases(), 'value')),
            ],

            'due_date' => ['nullable', 'date'],
        ];
    }
}
