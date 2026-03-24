<?php

namespace App\Modules\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyPostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:post_categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách danh mục không được để trống.',
            'ids.array' => 'Danh sách danh mục phải là một mảng.',
            'ids.min' => 'Danh sách danh mục phải có ít nhất 1 danh mục.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
