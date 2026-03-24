<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:65535',
            'status' => ['sometimes', DocumentStatusEnum::rule()],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Tên danh mục.',
                'example' => 'Lĩnh vực pháp lý',
            ],
            'description' => [
                'description' => 'Mô tả danh mục.',
                'example' => 'Cập nhật mô tả danh mục.',
            ],
            'status' => [
                'description' => 'Trạng thái mới.',
                'example' => DocumentStatusEnum::Active->value,
            ],
        ];
    }
}
