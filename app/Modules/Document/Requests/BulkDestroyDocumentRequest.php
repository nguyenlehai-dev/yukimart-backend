<?php

namespace App\Modules\Document\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:documents,id',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
