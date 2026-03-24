<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStatusCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'status' => ['required', DocumentStatusEnum::rule()],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'ids' => [
                'description' => 'Danh sách ID cần cập nhật trạng thái.',
                'example' => [1, 2, 3],
            ],
            'status' => [
                'description' => 'Trạng thái mới áp dụng hàng loạt.',
                'example' => DocumentStatusEnum::Inactive->value,
            ],
        ];
    }
}
