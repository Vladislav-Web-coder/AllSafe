<?php

namespace App\Interfaces\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => [
                'required',
                'integer',
                'exists:pgsql_core.document_types,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }
}
