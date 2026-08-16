<?php

namespace App\Interfaces\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class AddTaskCommentRequest extends FormRequest
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
