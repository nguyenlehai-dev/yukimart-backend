<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Inventory;
use App\Modules\Product\Models\InventoryTransaction;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Lấy tồn kho theo chi nhánh cho 1 sản phẩm.
     */
    public function getInventoryByProduct(int $productId)
    {
        return Inventory::where('product_id', $productId)
            ->with(['organization:id,name', 'variant:id,name,sku'])
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'organization_id' => $inv->organization_id,
                'organization_name' => $inv->organization?->name,
                'variant_id' => $inv->variant_id,
                'variant_name' => $inv->variant?->name,
                'variant_sku' => $inv->variant?->sku,
                'quantity' => (float) $inv->quantity,
                'cost_price' => (float) $inv->cost_price,
            ]);
    }

    /**
     * Lấy thẻ kho (lịch sử giao dịch) cho 1 sản phẩm.
     */
    public function getStockCard(int $productId, array $filters, int $perPage = 20)
    {
        $inventoryIds = Inventory::where('product_id', $productId)
            ->when($filters['organization_id'] ?? null, function ($q, $orgId) {
                $q->where('organization_id', $orgId);
            })
            ->pluck('id');

        return InventoryTransaction::whereIn('inventory_id', $inventoryIds)
            ->with(['inventory.organization:id,name', 'inventory.variant:id,name,sku', 'creator:id,name'])
            ->when($filters['type'] ?? null, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($filters['from_date'] ?? null, function ($q, $date) {
                $q->where('created_at', '>=', $date);
            })
            ->when($filters['to_date'] ?? null, function ($q, $date) {
                $q->where('created_at', '<=', $date.' 23:59:59');
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Phân tích hiệu quả kinh doanh cơ bản.
     */
    public function getAnalytics(int $productId, array $filters)
    {
        $inventoryIds = Inventory::where('product_id', $productId)
            ->when($filters['organization_id'] ?? null, function ($q, $orgId) {
                $q->where('organization_id', $orgId);
            })
            ->pluck('id');

        $query = InventoryTransaction::whereIn('inventory_id', $inventoryIds);

        if (! empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date'].' 23:59:59');
        }

        $sales = (clone $query)->where('type', 'sale');
        $returns = (clone $query)->where('type', 'return');
        $imports = (clone $query)->where('type', 'import');

        $soldQty = abs((float) $sales->sum('quantity_change'));
        $returnQty = abs((float) $returns->sum('quantity_change'));
        $importQty = abs((float) $imports->sum('quantity_change'));

        $product = Product::find($productId);
        $totalInventory = Inventory::where('product_id', $productId)->sum('quantity');

        return [
            'product_id' => $productId,
            'product_name' => $product?->name,
            'sold_quantity' => $soldQty,
            'return_quantity' => $returnQty,
            'import_quantity' => $importQty,
            'revenue' => round($soldQty * (float) $product?->base_price, 2),
            'cost' => round($soldQty * (float) $product?->cost_price, 2),
            'profit' => round($soldQty * ((float) $product?->base_price - (float) $product?->cost_price), 2),
            'return_value' => round($returnQty * (float) $product?->base_price, 2),
            'current_stock' => (float) $totalInventory,
        ];
    }

    /**
     * Thêm giao dịch tồn kho (dùng bởi modules khác: bán hàng, nhập hàng...).
     */
    public function addTransaction(
        int $productId,
        ?int $variantId,
        int $organizationId,
        string $type,
        float $quantityChange,
        float $costPrice = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
    ): InventoryTransaction {
        return DB::transaction(function () use (
            $productId, $variantId, $organizationId, $type,
            $quantityChange, $costPrice, $referenceType, $referenceId, $note,
        ) {
            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'organization_id' => $organizationId,
                ],
                ['quantity' => 0, 'cost_price' => 0]
            );

            $inventory->increment('quantity', $quantityChange);

            // Cập nhật giá vốn trung bình khi nhập hàng
            if ($type === 'import' && $quantityChange > 0 && $costPrice > 0) {
                $totalCostBefore = ($inventory->quantity - $quantityChange) * $inventory->cost_price;
                $totalCostNew = $quantityChange * $costPrice;
                $inventory->cost_price = ($totalCostBefore + $totalCostNew) / $inventory->quantity;
                $inventory->save();
            }

            return InventoryTransaction::create([
                'inventory_id' => $inventory->id,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'quantity_after' => $inventory->quantity,
                'cost_price' => $costPrice ?: $inventory->cost_price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
            ]);
        });
    }
}
