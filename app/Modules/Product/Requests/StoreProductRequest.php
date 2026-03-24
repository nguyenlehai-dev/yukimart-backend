<?php

namespace App\Modules\Product\Requests;

use App\Modules\Product\Enums\ProductTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', ProductTypeEnum::values())],
            'code' => ['nullable', 'string', 'max:50', 'unique:products,code'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'base_unit_id' => ['nullable', 'integer', 'exists:product_units,id'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'point' => ['nullable', 'integer', 'min:0'],

            // Relations
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],

            // Variants
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'exists:product_attribute_values,id'],

            // Components (combo / manufacturing)
            'components' => ['nullable', 'array'],
            'components.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'components.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'components.*.unit_id' => ['nullable', 'integer', 'exists:product_units,id'],

            // Unit conversions
            'unit_conversions' => ['nullable', 'array'],
            'unit_conversions.*.unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'unit_conversions.*.conversion_value' => ['required', 'numeric', 'min:0.001'],
            'unit_conversions.*.price' => ['nullable', 'numeric', 'min:0'],
            'unit_conversions.*.barcode' => ['nullable', 'string', 'max:100'],
        ];
    }
}
