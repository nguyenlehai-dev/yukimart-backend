<?php

namespace App\Modules\Document\Requests;

use App\Modules\Document\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', DocumentStatusEnum::rule()],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'Trạng thái cần chuyển.',
                'example' => DocumentStatusEnum::Inactive->value,
            ],
        ];
    }
}
