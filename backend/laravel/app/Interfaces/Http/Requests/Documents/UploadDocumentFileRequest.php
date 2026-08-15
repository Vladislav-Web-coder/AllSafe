<?php

namespace App\Interfaces\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200', // 50 MB
                'mimes:pdf,doc,docx,txt,rtf,md',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
