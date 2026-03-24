<?php

namespace App\Modules\Post\Requests;

use App\Modules\Core\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStatusPostCategoryRequest extends FormRequest
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
            'status' => ['required', StatusEnum::rule()],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Danh sách danh mục không được để trống.',
            'ids.array' => 'Danh sách danh mục phải là một mảng.',
            'ids.min' => 'Danh sách danh mục phải có ít nhất 1 danh mục.',
            'status.required' => 'Trạng thái không được để trống.',
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận active, inactive.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
