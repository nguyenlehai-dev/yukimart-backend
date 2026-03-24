<?php

namespace App\Modules\Post\Requests;

use App\Modules\Post\Enums\PostStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', PostStatusEnum::rule()],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ. Chỉ chấp nhận draft, published, archived.',
        ];
    }

    public function bodyParameters()
    {
        return [
            'status' => [
                'description' => 'Trạng thái mới của bài viết.',
                'example' => 'published',
            ],
        ];
    }
}
