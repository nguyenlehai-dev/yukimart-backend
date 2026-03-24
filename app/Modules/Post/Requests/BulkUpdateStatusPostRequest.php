<?php

namespace App\Modules\Post\Requests;

use App\Modules\Post\Enums\PostStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStatusPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:posts,id',
            'status' => ['required', PostStatusEnum::rule()],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Bạn chưa chọn bài viết nào.',
            'ids.*.exists' => 'Một trong các bài viết không tồn tại.',
            'status.required' => 'Trạng thái không được để trống.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
