<?php

namespace App\Interfaces\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskFromIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_id' => ['required', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
