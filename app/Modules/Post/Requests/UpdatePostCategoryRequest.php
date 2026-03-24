<?php

namespace App\Modules\Post\Requests;

use App\Modules\Core\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = is_object($category) ? $category->id : $category;

        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:post_categories,slug,'.$categoryId,
            'description' => 'nullable|string|max:65535',
            'status' => ['sometimes', StatusEnum::rule()],
            'sort_order' => 'nullable|integer|min:0',
            'parent_id' => [
                'nullable',
                Rule::notIn([$categoryId]),
                Rule::when($this->filled('parent_id') && (int) $this->parent_id !== 0, ['exists:post_categories,id']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug danh mục đã tồn tại.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
