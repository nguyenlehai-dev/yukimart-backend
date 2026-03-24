<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\PriceList;
use App\Modules\Product\Models\PriceListItem;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class PriceListService
{
    public function list(array $filters, int $perPage = 20)
    {
        return PriceList::withCount('items')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): PriceList
    {
        return PriceList::with(['items.product:id,code,name', 'items.variant:id,name,sku', 'items.unit:id,name', 'organizations:id,name', 'basePriceList:id,name'])->findOrFail($id);
    }

    public function store(array $data): PriceList
    {
        return DB::transaction(function () use ($data) {
            $priceList = PriceList::create($data);

            $this->syncOrganizations($priceList, $data);

            // Thêm hàng hóa từ bảng giá gốc nếu được cấu hình
            if ($priceList->add_products_from_base && $priceList->base_price_list_id) {
                $this->addItemsFromBase($priceList);
            }

            return $this->find($priceList->id);
        });
    }

    public function update(PriceList $priceList, array $data): PriceList
    {
        return DB::transaction(function () use ($priceList, $data) {
            $priceList->update($data);
            $this->syncOrganizations($priceList, $data);

            return $this->find($priceList->id);
        });
    }

    public function destroy(PriceList $priceList): bool
    {
        if ($priceList->is_default) {
            throw new \Exception('Không thể xóa bảng giá mặc định.');
        }

        return DB::transaction(fn () => $priceList->delete());
    }

    // ── Items ──

    /**
     * Thêm/cập nhật sản phẩm vào bảng giá.
     */
    public function upsertItems(PriceList $priceList, array $items): PriceList
    {
        return DB::transaction(function () use ($priceList, $items) {
            foreach ($items as $item) {
                PriceListItem::updateOrCreate(
                    [
                        'price_list_id' => $priceList->id,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'unit_id' => $item['unit_id'] ?? null,
                    ],
                    [
                        'price' => $item['price'] ?? 0,
                        'item_formula_type' => $item['item_formula_type'] ?? null,
                        'item_formula_value' => $item['item_formula_value'] ?? null,
                    ]
                );
            }

            return $this->find($priceList->id);
        });
    }

    /**
     * Xóa sản phẩm khỏi bảng giá.
     */
    public function removeItems(PriceList $priceList, array $itemIds): int
    {
        return $priceList->items()->whereIn('id', $itemIds)->delete();
    }

    /**
     * Thêm tất cả sản phẩm vào bảng giá.
     */
    public function addAllProducts(PriceList $priceList): int
    {
        $existingProductIds = $priceList->items()->pluck('product_id')->toArray();
        $products = Product::whereNotIn('id', $existingProductIds)
            ->where('is_active', true)
            ->get(['id', 'base_price']);

        $count = 0;
        foreach ($products as $product) {
            $price = $priceList->base_price_list_id
                ? $this->getBasePrice($priceList, $product->id)
                : $product->base_price;

            $priceList->items()->create([
                'product_id' => $product->id,
                'price' => $priceList->calculatePrice((float) $price),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Thêm sản phẩm theo nhóm hàng.
     */
    public function addByCategory(PriceList $priceList, int $categoryId): int
    {
        $existingProductIds = $priceList->items()->pluck('product_id')->toArray();
        $products = Product::where('category_id', $categoryId)
            ->whereNotIn('id', $existingProductIds)
            ->where('is_active', true)
            ->get(['id', 'base_price']);

        $count = 0;
        foreach ($products as $product) {
            $price = $priceList->calculatePrice((float) $product->base_price);
            $priceList->items()->create([
                'product_id' => $product->id,
                'price' => $price,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Áp dụng công thức cho tất cả items trong bảng giá.
     */
    public function applyFormulaToAll(PriceList $priceList, string $formulaType, float $formulaValue, ?int $basePriceListId = null): int
    {
        return DB::transaction(function () use ($priceList, $formulaType, $formulaValue, $basePriceListId) {
            $items = $priceList->items()->with('product:id,base_price')->get();
            $count = 0;

            foreach ($items as $item) {
                $basePrice = $basePriceListId
                    ? $this->getItemBasePrice($basePriceListId, $item->product_id, $item->variant_id, $item->unit_id)
                    : (float) $item->product->base_price;

                $item->price = $priceList->calculatePrice($basePrice, $formulaType, $formulaValue);
                $item->save();
                $count++;
            }

            return $count;
        });
    }

    /**
     * So sánh nhiều bảng giá (tối đa 5).
     */
    public function compare(array $priceListIds, array $filters = [], int $perPage = 20)
    {
        $query = Product::with(['category:id,name', 'baseUnit:id,name'])
            ->where('is_active', true);

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $products = $query->paginate($perPage);

        // Lấy giá từ các bảng giá
        $productIds = $products->pluck('id')->toArray();
        $priceItems = PriceListItem::whereIn('price_list_id', $priceListIds)
            ->whereIn('product_id', $productIds)
            ->whereNull('variant_id')
            ->get()
            ->groupBy('product_id');

        // Map giá vào từng product
        $products->getCollection()->transform(function ($product) use ($priceListIds, $priceItems) {
            $prices = [];
            foreach ($priceListIds as $plId) {
                $item = ($priceItems[$product->id] ?? collect())->firstWhere('price_list_id', $plId);
                $prices["price_list_{$plId}"] = $item ? (float) $item->price : null;
            }
            $product->price_comparison = $prices;

            return $product;
        });

        return $products;
    }

    // ── Private helpers ──

    protected function syncOrganizations(PriceList $priceList, array $data): void
    {
        if (array_key_exists('organization_ids', $data)) {
            $priceList->organizations()->sync($data['organization_ids'] ?? []);
        }
    }

    protected function addItemsFromBase(PriceList $priceList): void
    {
        $baseItems = PriceListItem::where('price_list_id', $priceList->base_price_list_id)->get();

        foreach ($baseItems as $baseItem) {
            $priceList->items()->create([
                'product_id' => $baseItem->product_id,
                'variant_id' => $baseItem->variant_id,
                'unit_id' => $baseItem->unit_id,
                'price' => $priceList->calculatePrice((float) $baseItem->price),
            ]);
        }
    }

    protected function getBasePrice(PriceList $priceList, int $productId): float
    {
        $baseItem = PriceListItem::where('price_list_id', $priceList->base_price_list_id)
            ->where('product_id', $productId)
            ->whereNull('variant_id')
            ->first();

        return $baseItem ? (float) $baseItem->price : 0;
    }

    protected function getItemBasePrice(int $basePriceListId, int $productId, ?int $variantId, ?int $unitId): float
    {
        $item = PriceListItem::where('price_list_id', $basePriceListId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('unit_id', $unitId)
            ->first();

        if ($item) {
            return (float) $item->price;
        }

        // Fallback: lấy giá sản phẩm gốc
        $product = Product::find($productId);

        return $product ? (float) $product->base_price : 0;
    }
}
