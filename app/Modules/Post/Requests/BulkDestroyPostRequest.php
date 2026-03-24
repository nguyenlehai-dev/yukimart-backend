<?php

namespace App\Modules\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyPostRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Bạn chưa chọn bài viết nào.',
            'ids.*.exists' => 'Một trong các bài viết không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [];
    }
}
