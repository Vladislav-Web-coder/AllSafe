<?php

namespace App\Interfaces\Http\Requests\Tasks;

use App\Domain\Tasks\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],

            'priority' => [
                'nullable',
                'string',
                Rule::in(array_column(TaskPriority::cases(), 'value')),
            ],

            'document_issue_id' => ['nullable', 'integer'],
            'document_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],

            // Дата должна быть не раньше текущего момента
            'due_date' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'Срок выполнения не может быть в прошлом.',
        ];
    }
}
