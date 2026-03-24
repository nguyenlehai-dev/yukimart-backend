<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\StockCheck;
use App\Modules\Product\Models\Inventory;
use App\Modules\Product\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class StockCheckService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function list(array $filters, int $perPage = 20)
    {
        return StockCheck::with(['organization:id,name', 'creator:id,name'])
            ->withCount('items')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): StockCheck
    {
        return StockCheck::with([
            'organization:id,name',
            'items.product:id,code,name,cost_price',
            'items.variant:id,name,sku',
            'items.unit:id,name',
            'creator:id,name',
        ])->findOrFail($id);
    }

    /**
     * Tạo phiếu kiểm kho (lưu tạm hoặc hoàn thành + cân bằng kho).
     */
    public function store(array $data): StockCheck
    {
        return DB::transaction(function () use ($data) {
            $check = StockCheck::create([
                'organization_id' => $data['organization_id'],
                'status' => 'draft',
                'note' => $data['note'] ?? null,
                'check_date' => $data['check_date'] ?? now(),
            ]);

            $this->upsertItems($check, $data['items']);

            // Nếu yêu cầu hoàn thành luôn
            if (($data['status'] ?? 'draft') === 'balanced') {
                return $this->balance($check);
            }

            return $this->find($check->id);
        });
    }

    /**
     * Cập nhật items phiếu tạm.
     */
    public function updateDraft(StockCheck $check, array $data): StockCheck
    {
        if ($check->status !== 'draft') {
            throw new \Exception('Chi co the cap nhat phieu tam.');
        }

        return DB::transaction(function () use ($check, $data) {
            if (isset($data['note'])) {
                $check->update(['note' => $data['note']]);
            }
            if (isset($data['items'])) {
                $check->items()->delete();
                $this->upsertItems($check, $data['items']);
            }

            return $this->find($check->id);
        });
    }

    /**
     * Hoàn thành + Cân bằng kho: cập nhật tồn kho = số lượng thực tế.
     */
    public function balance(StockCheck $check): StockCheck
    {
        if ($check->status !== 'draft') {
            throw new \Exception('Chi co the can bang kho tu phieu tam.');
        }

        return DB::transaction(function () use ($check) {
            $items = $check->items()->with('product')->get();

            foreach ($items as $item) {
                $deviation = (float) $item->deviation;
                if ($deviation == 0) {
                    continue;
                }

                $this->inventoryService->addTransaction(
                    productId: $item->product_id,
                    organizationId: $check->organization_id,
                    type: 'stock_check',
                    quantity: $deviation,
                    price: (float) $item->cost_price,
                    referenceType: 'stock_check',
                    referenceId: $check->id,
                    note: "Kiem kho #{$check->code} (chenh lech: {$deviation})",
                    variantId: $item->variant_id,
                );
            }

            $check->update([
                'status' => 'balanced',
                'balanced_at' => now(),
            ]);

            return $this->find($check->id);
        });
    }

    /**
     * Hủy phiếu đã cân bằng → hoàn lại tồn kho.
     */
    public function cancel(StockCheck $check): StockCheck
    {
        if ($check->status === 'cancelled') {
            throw new \Exception('Phieu da bi huy truoc do.');
        }

        return DB::transaction(function () use ($check) {
            if ($check->status === 'balanced') {
                $items = $check->items()->get();
                foreach ($items as $item) {
                    $deviation = (float) $item->deviation;
                    if ($deviation == 0) {
                        continue;
                    }

                    $this->inventoryService->addTransaction(
                        productId: $item->product_id,
                        organizationId: $check->organization_id,
                        type: 'stock_check',
                        quantity: -$deviation,
                        price: (float) $item->cost_price,
                        referenceType: 'stock_check',
                        referenceId: $check->id,
                        note: "Huy kiem kho #{$check->code}",
                        variantId: $item->variant_id,
                    );
                }
            }

            $check->update(['status' => 'cancelled']);

            return $this->find($check->id);
        });
    }

    /**
     * Sao chép phiếu.
     */
    public function copy(StockCheck $check): StockCheck
    {
        $items = $check->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'unit_id' => $item->unit_id,
            'actual_quantity' => $item->actual_quantity,
        ])->toArray();

        return $this->store([
            'organization_id' => $check->organization_id,
            'status' => 'draft',
            'note' => "Sao chep tu phieu {$check->code}",
            'items' => $items,
        ]);
    }

    /**
     * Gộp nhiều phiếu tạm thành 1 phiếu.
     */
    public function merge(array $checkIds): StockCheck
    {
        $checks = StockCheck::whereIn('id', $checkIds)->where('status', 'draft')->get();

        if ($checks->count() < 2) {
            throw new \Exception('Can it nhat 2 phieu tam de gop.');
        }

        $orgId = $checks->first()->organization_id;

        // Gom items, cộng dồn actual_quantity theo product+variant
        $merged = [];
        foreach ($checks as $check) {
            foreach ($check->items as $item) {
                $key = "{$item->product_id}_{$item->variant_id}_{$item->unit_id}";
                if (isset($merged[$key])) {
                    $merged[$key]['actual_quantity'] += (float) $item->actual_quantity;
                } else {
                    $merged[$key] = [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'unit_id' => $item->unit_id,
                        'actual_quantity' => (float) $item->actual_quantity,
                    ];
                }
            }
        }

        return $this->store([
            'organization_id' => $orgId,
            'status' => 'draft',
            'note' => 'Gop tu phieu: ' . $checks->pluck('code')->implode(', '),
            'items' => array_values($merged),
        ]);
    }

    // ── Private ──

    protected function upsertItems(StockCheck $check, array $items): void
    {
        $totalDevAmount = 0;
        $totalIncrease = 0;
        $totalDecrease = 0;

        foreach ($items as $item) {
            // Lấy tồn kho hiện tại
            $systemQty = $this->getSystemQuantity(
                $item['product_id'],
                $check->organization_id,
                $item['variant_id'] ?? null,
            );

            $actualQty = (float) ($item['actual_quantity'] ?? 0);
            $deviation = $actualQty - $systemQty;
            $costPrice = (float) ($item['cost_price'] ?? $this->getProductCostPrice($item['product_id']));
            $devAmount = $deviation * $costPrice;

            $check->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'system_quantity' => $systemQty,
                'actual_quantity' => $actualQty,
                'deviation' => $deviation,
                'cost_price' => $costPrice,
                'deviation_amount' => $devAmount,
                'reason' => $item['reason'] ?? null,
            ]);

            $totalDevAmount += $devAmount;
            if ($deviation > 0) $totalIncrease++;
            if ($deviation < 0) $totalDecrease++;
        }

        $check->update([
            'total_deviation_amount' => $totalDevAmount,
            'total_increase' => $totalIncrease,
            'total_decrease' => $totalDecrease,
        ]);
    }

    protected function getSystemQuantity(int $productId, int $orgId, ?int $variantId): float
    {
        $inv = Inventory::where('product_id', $productId)
            ->where('organization_id', $orgId)
            ->first();

        return $inv ? (float) $inv->quantity : 0;
    }

    protected function getProductCostPrice(int $productId): float
    {
        $product = \App\Modules\Product\Models\Product::find($productId);

        return $product ? (float) ($product->cost_price ?? 0) : 0;
    }
}
