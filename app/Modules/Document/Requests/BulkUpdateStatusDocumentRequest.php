<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStatusDocumentRequest extends FormRequest
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
            'status' => ['required', DocumentStatusEnum::rule()],
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
