<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function list(array $filters, int $perPage = 20)
    {
        return Product::with(['category:id,name', 'brand:id,name', 'baseUnit:id,name'])
            ->withCount('variants')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with([
            'category:id,name,slug',
            'brand:id,name,slug',
            'baseUnit:id,name',
            'variants.attributeValues.attribute',
            'components.componentProduct:id,code,name',
            'components.unit:id,name',
            'locations:id,name',
            'unitConversions',
            'media',
        ])->findOrFail($id);
    }

    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            $this->syncRelations($product, $data);

            return $this->find($product->id);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            $this->syncRelations($product, $data);

            // Xóa ảnh nếu được yêu cầu
            if (! empty($data['remove_image_ids'])) {
                $product->media()
                    ->whereIn('id', $data['remove_image_ids'])
                    ->each(fn ($media) => $media->delete());
            }

            return $this->find($product->id);
        });
    }

    public function destroy(Product $product): bool
    {
        $totalInventory = $product->inventories()->sum('quantity');
        if ($totalInventory != 0) {
            throw new \Exception('Không thể xóa hàng hóa có tồn kho khác 0. Vui lòng kiểm kho về 0 trước.');
        }

        return DB::transaction(fn () => $product->delete());
    }

    public function copy(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $newData = $product->replicate(['code', 'slug', 'barcode'])->toArray();
            $newData['name'] = $product->name.' (copy)';
            $newData['code'] = Product::generateCode($product->type);

            $newProduct = Product::create($newData);

            // Copy locations
            $newProduct->locations()->sync($product->locations->pluck('id'));

            // Copy components
            foreach ($product->components as $component) {
                $newProduct->components()->create([
                    'component_product_id' => $component->component_product_id,
                    'quantity' => $component->quantity,
                    'unit_id' => $component->unit_id,
                ]);
            }

            // Copy variants
            foreach ($product->variants as $variant) {
                $newVariant = $newProduct->variants()->create([
                    'sku' => $newProduct->code.'-'.uniqid(),
                    'barcode' => null,
                    'price' => $variant->price,
                    'cost_price' => $variant->cost_price,
                    'name' => $variant->name,
                ]);
                $newVariant->attributeValues()->sync($variant->attributeValues->pluck('id'));
            }

            // Copy unit conversions
            foreach ($product->unitConversions as $unitConversion) {
                $newProduct->unitConversions()->attach($unitConversion->id, [
                    'conversion_value' => $unitConversion->pivot->conversion_value,
                    'price' => $unitConversion->pivot->price,
                    'barcode' => null,
                ]);
            }

            return $this->find($newProduct->id);
        });
    }

    public function toggleActive(Product $product): Product
    {
        $product->update(['is_active' => ! $product->is_active]);

        return $product->fresh();
    }

    public function bulkToggleActive(array $ids, bool $isActive): int
    {
        return Product::whereIn('id', $ids)->update(['is_active' => $isActive]);
    }

    public function bulkCategory(array $ids, int $categoryId): int
    {
        return Product::whereIn('id', $ids)->update(['category_id' => $categoryId]);
    }

    public function bulkPoint(array $ids, int $point): int
    {
        return Product::whereIn('id', $ids)->update(['point' => $point]);
    }

    public function bulkDelete(array $ids): int
    {
        $products = Product::whereIn('id', $ids)->get();
        $deleted = 0;

        DB::transaction(function () use ($products, &$deleted) {
            foreach ($products as $product) {
                $totalInventory = $product->inventories()->sum('quantity');
                if ($totalInventory == 0) {
                    $product->delete();
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    // ── Private helpers ──

    protected function syncRelations(Product $product, array $data): void
    {
        // Locations
        if (array_key_exists('location_ids', $data)) {
            $product->locations()->sync($data['location_ids'] ?? []);
        }

        // Components (combo / manufacturing)
        if (array_key_exists('components', $data)) {
            $product->components()->delete();
            foreach ($data['components'] ?? [] as $comp) {
                $product->components()->create([
                    'component_product_id' => $comp['product_id'],
                    'quantity' => $comp['quantity'],
                    'unit_id' => $comp['unit_id'] ?? null,
                ]);
            }
        }

        // Variants
        if (array_key_exists('variants', $data)) {
            // Xóa variant cũ và tạo mới
            $product->variants()->delete();
            foreach ($data['variants'] ?? [] as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'] ?? $product->code.'-'.uniqid(),
                    'barcode' => $variantData['barcode'] ?? null,
                    'price' => $variantData['price'] ?? $product->base_price,
                    'cost_price' => $variantData['cost_price'] ?? $product->cost_price,
                    'name' => $variantData['name'] ?? '',
                ]);

                if (! empty($variantData['attribute_value_ids'])) {
                    $variant->attributeValues()->sync($variantData['attribute_value_ids']);
                }
            }
        }

        // Unit conversions
        if (array_key_exists('unit_conversions', $data)) {
            $product->unitConversions()->detach();
            foreach ($data['unit_conversions'] ?? [] as $uc) {
                $product->unitConversions()->attach($uc['unit_id'], [
                    'conversion_value' => $uc['conversion_value'] ?? 1,
                    'price' => $uc['price'] ?? null,
                    'barcode' => $uc['barcode'] ?? null,
                ]);
            }
        }

        // Images
        if (! empty($data['images'])) {
            foreach ($data['images'] as $image) {
                $product->addMedia($image)->toMediaCollection('product-images');
            }
        }
    }
}
