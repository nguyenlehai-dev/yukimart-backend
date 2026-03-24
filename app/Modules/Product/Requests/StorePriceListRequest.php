<?php

namespace App\Modules\Product\Requests;

use App\Modules\Product\Enums\PriceListStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(PriceListStatusEnum::values())],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'base_price_list_id' => 'nullable|exists:price_lists,id',
            'formula_type' => 'nullable|in:percentage,fixed_amount',
            'formula_value' => 'nullable|numeric',
            'auto_update_from_base' => 'nullable|boolean',
            'add_products_from_base' => 'nullable|boolean',
            'rounding_type' => 'nullable|in:none,unit,ten,hundred,thousand,ten_thousand',
            'rounding_method' => 'nullable|in:round,ceil,floor',
            'cashier_policy' => 'nullable|in:allow_all,allow_with_warning,only_in_list',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
        ];
    }
}
