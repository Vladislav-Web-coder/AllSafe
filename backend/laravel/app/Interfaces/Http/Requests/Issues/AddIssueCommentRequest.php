<?php

namespace App\Interfaces\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;

class AddIssueCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
