<?php

namespace App\Modules\Document\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'ids' => [
                'description' => 'Danh sách ID cần xóa.',
                'example' => [1, 2, 3],
            ],
        ];
    }
}
