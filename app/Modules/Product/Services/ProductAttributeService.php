<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\ProductAttribute;
use App\Modules\Product\Models\ProductAttributeValue;
use Illuminate\Support\Facades\DB;

class ProductAttributeService
{
    public function list()
    {
        return ProductAttribute::with('values')->orderBy('sort_order')->get();
    }

    public function store(array $data): ProductAttribute
    {
        return DB::transaction(function () use ($data) {
            $attribute = ProductAttribute::create([
                'name' => $data['name'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if (! empty($data['values'])) {
                foreach ($data['values'] as $i => $value) {
                    $attribute->values()->create([
                        'value' => $value,
                        'sort_order' => $i,
                    ]);
                }
            }

            return $attribute->load('values');
        });
    }

    public function update(ProductAttribute $attribute, array $data): ProductAttribute
    {
        return DB::transaction(function () use ($attribute, $data) {
            $attribute->update([
                'name' => $data['name'] ?? $attribute->name,
                'sort_order' => $data['sort_order'] ?? $attribute->sort_order,
            ]);

            if (array_key_exists('values', $data)) {
                // Xóa values cũ và tạo lại
                $attribute->values()->delete();
                foreach ($data['values'] as $i => $value) {
                    $attribute->values()->create([
                        'value' => $value,
                        'sort_order' => $i,
                    ]);
                }
            }

            return $attribute->load('values');
        });
    }

    public function destroy(ProductAttribute $attribute): bool
    {
        return DB::transaction(fn () => $attribute->delete());
    }
}
