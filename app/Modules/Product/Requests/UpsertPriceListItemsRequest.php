<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPriceListItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.item_formula_type' => 'nullable|in:percentage,fixed_amount',
            'items.*.item_formula_value' => 'nullable|numeric',
        ];
    }
}
