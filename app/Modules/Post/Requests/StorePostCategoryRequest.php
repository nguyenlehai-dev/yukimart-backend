<?php

namespace App\Modules\Post\Requests;

use App\Modules\Core\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StorePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:post_categories,slug',
            'description' => 'nullable|string|max:65535',
            'status' => ['required', StatusEnum::rule()],
            'sort_order' => 'nullable|integer|min:0',
            'parent_id' => 'nullable|exists:post_categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục không được để trống.',
            'slug.unique' => 'Slug danh mục đã tồn tại.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
