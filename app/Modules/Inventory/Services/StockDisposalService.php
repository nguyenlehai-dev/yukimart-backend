<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\StockDisposal;
use App\Modules\Product\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class StockDisposalService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function list(array $filters, int $perPage = 20)
    {
        return StockDisposal::with(['organization:id,name', 'creator:id,name'])
            ->withCount('items')
            ->filter($filters)
            ->paginate($perPage);
    }

    public function find(int $id): StockDisposal
    {
        return StockDisposal::with([
            'organization:id,name',
            'items.product:id,code,name,cost_price',
            'items.variant:id,name,sku',
            'items.unit:id,name',
            'creator:id,name',
        ])->findOrFail($id);
    }

    public function store(array $data): StockDisposal
    {
        return DB::transaction(function () use ($data) {
            $disposal = StockDisposal::create([
                'organization_id' => $data['organization_id'],
                'status' => $data['status'] ?? 'completed',
                'note' => $data['note'] ?? null,
                'disposal_date' => $data['disposal_date'] ?? now(),
            ]);

            $totalAmount = $this->createItems($disposal, $data['items']);
            $disposal->update(['total_amount' => $totalAmount]);

            if ($disposal->status === 'completed') {
                $this->adjustInventory($disposal, 'subtract');
            }

            return $this->find($disposal->id);
        });
    }

    public function update(StockDisposal $disposal, array $data): StockDisposal
    {
        $disposal->update([
            'note' => $data['note'] ?? $disposal->note,
            'disposal_date' => $data['disposal_date'] ?? $disposal->disposal_date,
        ]);

        return $this->find($disposal->id);
    }

    public function cancel(StockDisposal $disposal): StockDisposal
    {
        if ($disposal->status === 'cancelled') {
            throw new \Exception('Phieu da bi huy truoc do.');
        }

        return DB::transaction(function () use ($disposal) {
            if ($disposal->status === 'completed') {
                $this->adjustInventory($disposal, 'add');
            }
            $disposal->update(['status' => 'cancelled']);

            return $this->find($disposal->id);
        });
    }

    public function copy(StockDisposal $disposal): StockDisposal
    {
        $items = $disposal->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'unit_id' => $item->unit_id,
            'quantity' => $item->quantity,
            'cost_price' => $item->cost_price,
            'reason' => $item->reason,
        ])->toArray();

        return $this->store([
            'organization_id' => $disposal->organization_id,
            'status' => 'draft',
            'note' => "Sao chep tu phieu {$disposal->code}",
            'items' => $items,
        ]);
    }

    public function complete(StockDisposal $disposal): StockDisposal
    {
        if ($disposal->status !== 'draft') {
            throw new \Exception('Chi co the hoan thanh phieu tam.');
        }

        return DB::transaction(function () use ($disposal) {
            $disposal->update(['status' => 'completed']);
            $this->adjustInventory($disposal, 'subtract');

            return $this->find($disposal->id);
        });
    }

    // ── Private ──

    protected function createItems(StockDisposal $disposal, array $items): float
    {
        $total = 0;
        foreach ($items as $item) {
            $amount = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
            $disposal->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'unit_id' => $item['unit_id'] ?? null,
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'] ?? 0,
                'amount' => $amount,
                'reason' => $item['reason'] ?? null,
            ]);
            $total += $amount;
        }

        return $total;
    }

    protected function adjustInventory(StockDisposal $disposal, string $direction): void
    {
        $items = $disposal->items()->get();

        foreach ($items as $item) {
            $qty = $direction === 'subtract' ? -abs($item->quantity) : abs($item->quantity);

            $this->inventoryService->addTransaction(
                productId: $item->product_id,
                organizationId: $disposal->organization_id,
                type: 'disposal',
                quantity: $qty,
                price: (float) $item->cost_price,
                referenceType: 'stock_disposal',
                referenceId: $disposal->id,
                note: $direction === 'subtract'
                    ? "Xuat huy #{$disposal->code}"
                    : "Huy phieu xuat huy #{$disposal->code}",
                variantId: $item->variant_id,
            );
        }
    }
}
