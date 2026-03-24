<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Product\Services\InventoryService;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function list(array $filters, int $perPage = 20)
    {
        return PurchaseOrder::with(['supplier:id,code,name', 'organization:id,name', 'creator:id,name'])
            ->withCount('items')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): PurchaseOrder
    {
        return PurchaseOrder::with([
            'supplier:id,code,name,phone,address',
            'organization:id,name',
            'items.product:id,code,name,base_price,cost_price',
            'items.variant:id,name,sku',
            'items.unit:id,name',
            'returns:id,code,status,total_amount',
        ])->findOrFail($id);
    }

    /**
     * Tạo phiếu nhập hàng + cộng tồn kho + cập nhật công nợ NCC.
     */
    public function store(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'organization_id' => $data['organization_id'],
                'status' => $data['status'] ?? 'completed',
                'discount' => $data['discount'] ?? 0,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'note' => $data['note'] ?? null,
                'order_date' => $data['order_date'] ?? now(),
            ]);

            $totalAmount = $this->createItems($order, $data['items']);
            $debtAmount = $totalAmount - ($data['discount'] ?? 0) - ($data['paid_amount'] ?? 0);

            $order->update([
                'total_amount' => $totalAmount,
                'debt_amount' => max(0, $debtAmount),
            ]);

            // Cộng tồn kho nếu trạng thái là completed
            if ($order->status === 'completed') {
                $this->adjustInventory($order, 'add');
                $this->updateSupplierDebt($order->supplier_id, max(0, $debtAmount));
            }

            return $this->find($order->id);
        });
    }

    /**
     * Cập nhật phiếu nhập (thông tin chung: NCC, ghi chú, ngày nhập).
     */
    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        $order->update([
            'supplier_id' => $data['supplier_id'] ?? $order->supplier_id,
            'note' => $data['note'] ?? $order->note,
            'order_date' => $data['order_date'] ?? $order->order_date,
        ]);

        return $this->find($order->id);
    }

    /**
     * Mở phiếu (chuyển về draft, trừ tồn kho, hoàn công nợ) để sửa toàn bộ.
     */
    public function reopen(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'completed') {
            throw new \Exception('Chỉ có thể mở phiếu đã hoàn thành.');
        }

        return DB::transaction(function () use ($order) {
            $this->adjustInventory($order, 'subtract');
            $this->updateSupplierDebt($order->supplier_id, -($order->debt_amount));
            $order->update(['status' => 'draft']);

            return $this->find($order->id);
        });
    }

    /**
     * Hoàn thành phiếu tạm (cộng tồn kho + công nợ).
     */
    public function complete(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'draft') {
            throw new \Exception('Chỉ có thể hoàn thành phiếu tạm.');
        }

        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'completed']);
            $this->adjustInventory($order, 'add');
            $this->updateSupplierDebt($order->supplier_id, $order->debt_amount);

            return $this->find($order->id);
        });
    }

    /**
     * Hủy phiếu nhập → trừ tồn kho + hoàn công nợ.
     */
    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status === 'cancelled') {
            throw new \Exception('Phiếu đã bị hủy trước đó.');
        }

        return DB::transaction(function () use ($order) {
            if ($order->status === 'completed') {
                $this->adjustInventory($order, 'subtract');
                $this->updateSupplierDebt($order->supplier_id, -($order->debt_amount));
            }

            $order->update(['status' => 'cancelled']);

            return $this->find($order->id);
        });
    }

    /**
     * Sao chép phiếu nhập → tạo phiếu tạm mới.
     */
    public function copy(PurchaseOrder $order): PurchaseOrder
    {
        $items = $order->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'unit_id' => $item->unit_id,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'discount' => $item->discount,
        ])->toArray();

        return $this->store([
            'supplier_id' => $order->supplier_id,
            'organization_id' => $order->organization_id,
            'status' => 'draft',
            'discount' => $order->discount,
            'paid_amount' => 0,
            'note' => "Sao chép từ phiếu {$order->code}",
            'items' => $items,
        ]);
    }

    // ── Private helpers ──

    protected function createItems(PurchaseOrder $order, array $items): float
    {
        $totalAmount = 0;
        foreach ($items as $item) {
            $amount = ($item['quantity'] ?? 0) * ($item['price'] ?? 0) - ($item['discount'] ?? 0);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'discount' => $item['discount'] ?? 0,
                'amount' => $amount,
            ]);
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    protected function adjustInventory(PurchaseOrder $order, string $direction): void
    {
        $items = $order->items()->with('product')->get();
        $type = $direction === 'add' ? 'import' : 'export';

        foreach ($items as $item) {
            $qty = $direction === 'add' ? abs($item->quantity) : -abs($item->quantity);

            $this->inventoryService->addTransaction(
                productId: $item->product_id,
                organizationId: $order->organization_id,
                type: $type,
                quantity: $qty,
                price: (float) $item->price,
                referenceType: 'purchase_order',
                referenceId: $order->id,
                note: $direction === 'add'
                    ? "Nhập hàng #{$order->code}"
                    : "Hủy nhập hàng #{$order->code}",
                variantId: $item->variant_id,
            );
        }
    }

    protected function updateSupplierDebt(?int $supplierId, float $amount): void
    {
        if (! $supplierId || $amount == 0) {
            return;
        }
        $supplier = Supplier::find($supplierId);
        if ($supplier) {
            $supplier->increment('debt', $amount);
        }
    }
}
