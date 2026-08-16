<?php

namespace App\Interfaces\Http\Requests\Generation;

use Illuminate\Foundation\Http\FormRequest;

class StartDocumentGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_template_id' => ['required', 'integer', 'exists:pgsql_core.document_templates,id'],
        ];
    }
}
