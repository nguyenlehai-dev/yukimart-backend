<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],

            // Dùng khi bulk-toggle-active
            'is_active' => ['sometimes', 'boolean'],

            // Dùng khi bulk-category
            'category_id' => ['sometimes', 'integer', 'exists:product_categories,id'],

            // Dùng khi bulk-point
            'point' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
