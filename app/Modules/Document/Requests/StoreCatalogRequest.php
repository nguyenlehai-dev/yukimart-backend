<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'status' => ['required', DocumentStatusEnum::rule()],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Tên danh mục.',
                'example' => 'Lĩnh vực tài chính',
            ],
            'description' => [
                'description' => 'Mô tả danh mục.',
                'example' => 'Danh mục dùng cho bộ lọc văn bản.',
            ],
            'status' => [
                'description' => 'Trạng thái danh mục.',
                'example' => DocumentStatusEnum::Active->value,
            ],
        ];
    }
}
