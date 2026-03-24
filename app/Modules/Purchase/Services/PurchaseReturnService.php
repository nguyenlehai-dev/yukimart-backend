<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Product\Services\InventoryService;
use App\Modules\Purchase\Models\PurchaseReturn;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function list(array $filters, int $perPage = 20)
    {
        return PurchaseReturn::with(['supplier:id,code,name', 'organization:id,name', 'creator:id,name'])
            ->withCount('items')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): PurchaseReturn
    {
        return PurchaseReturn::with([
            'supplier:id,code,name,phone',
            'organization:id,name',
            'purchaseOrder:id,code',
            'items.product:id,code,name,base_price',
            'items.variant:id,name,sku',
            'items.unit:id,name',
            'creator:id,name',
        ])->findOrFail($id);
    }

    /**
     * Trả hàng nhập nhanh (không theo phiếu nhập).
     */
    public function storeQuick(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $return = PurchaseReturn::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'organization_id' => $data['organization_id'],
                'purchase_order_id' => null,
                'status' => 'completed',
                'supplier_paid' => $data['supplier_paid'] ?? 0,
                'note' => $data['note'] ?? null,
                'return_date' => $data['return_date'] ?? now(),
            ]);

            $totalAmount = $this->createItems($return, $data['items']);

            $return->update([
                'total_amount' => $totalAmount,
                'debt_amount' => $totalAmount - ($data['supplier_paid'] ?? 0),
            ]);

            // Trừ tồn kho
            $this->adjustInventory($return, 'subtract');

            // Cập nhật công nợ NCC
            $this->updateSupplierDebt($return->supplier_id, -($return->debt_amount));

            return $this->find($return->id);
        });
    }

    /**
     * Trả hàng nhập theo phiếu nhập hàng.
     */
    public function storeFromPurchaseOrder(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

            $return = PurchaseReturn::create([
                'supplier_id' => $purchaseOrder->supplier_id,
                'organization_id' => $purchaseOrder->organization_id,
                'purchase_order_id' => $purchaseOrder->id,
                'status' => 'completed',
                'supplier_paid' => $data['supplier_paid'] ?? 0,
                'note' => $data['note'] ?? null,
                'return_date' => $data['return_date'] ?? now(),
            ]);

            $totalAmount = $this->createItems($return, $data['items']);

            $return->update([
                'total_amount' => $totalAmount,
                'debt_amount' => $totalAmount - ($data['supplier_paid'] ?? 0),
            ]);

            $this->adjustInventory($return, 'subtract');
            $this->updateSupplierDebt($return->supplier_id, -($return->debt_amount));

            return $this->find($return->id);
        });
    }

    /**
     * Cập nhật thông tin phiếu (người trả, ngày trả, ghi chú).
     */
    public function update(PurchaseReturn $return, array $data): PurchaseReturn
    {
        $return->update([
            'note' => $data['note'] ?? $return->note,
            'return_date' => $data['return_date'] ?? $return->return_date,
        ]);

        return $this->find($return->id);
    }

    /**
     * Hủy phiếu trả hàng → cộng lại tồn kho + cập nhật công nợ.
     */
    public function cancel(PurchaseReturn $return): PurchaseReturn
    {
        if ($return->status === 'cancelled') {
            throw new \Exception('Phiếu đã bị hủy trước đó.');
        }

        return DB::transaction(function () use ($return) {
            // Cộng lại tồn kho
            $this->adjustInventory($return, 'add');

            // Hoàn lại công nợ NCC
            $this->updateSupplierDebt($return->supplier_id, $return->debt_amount);

            $return->update(['status' => 'cancelled']);

            return $this->find($return->id);
        });
    }

    /**
     * Sao chép phiếu trả hàng.
     */
    public function copy(PurchaseReturn $return): PurchaseReturn
    {
        $items = $return->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'unit_id' => $item->unit_id,
            'quantity' => $item->quantity,
            'price' => $item->price,
        ])->toArray();

        return $this->storeQuick([
            'supplier_id' => $return->supplier_id,
            'organization_id' => $return->organization_id,
            'supplier_paid' => 0,
            'note' => "Sao chép từ phiếu {$return->code}",
            'items' => $items,
        ]);
    }

    // ── Private helpers ──

    protected function createItems(PurchaseReturn $return, array $items): float
    {
        $totalAmount = 0;
        foreach ($items as $item) {
            $amount = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
            $return->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $amount,
            ]);
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    protected function adjustInventory(PurchaseReturn $return, string $direction): void
    {
        $type = $direction === 'subtract' ? 'return' : 'import';
        $items = $return->items()->with('product')->get();

        foreach ($items as $item) {
            $qty = $direction === 'subtract' ? -abs($item->quantity) : abs($item->quantity);

            $this->inventoryService->addTransaction(
                productId: $item->product_id,
                organizationId: $return->organization_id,
                type: $type,
                quantity: $qty,
                price: (float) $item->price,
                referenceType: 'purchase_return',
                referenceId: $return->id,
                note: $direction === 'subtract'
                    ? "Trả hàng nhập #{$return->code}"
                    : "Hủy trả hàng nhập #{$return->code}",
                variantId: $item->variant_id,
            );
        }
    }

    protected function updateSupplierDebt(?int $supplierId, float $amount): void
    {
        if (! $supplierId) {
            return;
        }

        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $supplier->increment('debt', $amount);
        }
    }
}
