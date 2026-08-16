<?php

namespace App\Interfaces\Http\Requests\Issues;

use App\Domain\Analysis\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateIssuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_ids' => ['required', 'array', 'min:1', 'max:100'],
            'issue_ids.*' => ['integer'],

            'status' => [
                'required',
                'string',
                Rule::in(array_column(IssueStatus::cases(), 'value')),
            ],

            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
