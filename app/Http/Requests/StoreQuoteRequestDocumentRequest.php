<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreQuoteRequestDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $extensions = config('apoint.documents.allowed_extensions');

        return [
            'document' => [
                'required',
                'file',
                File::types($extensions)
                    ->max(config('apoint.documents.max_file_size_kb')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Please select a document to upload.',
            'document.file' => 'The selected document is invalid.',
        ];
    }
}
