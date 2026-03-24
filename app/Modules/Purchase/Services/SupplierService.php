<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Purchase\Models\Supplier;
use App\Modules\Purchase\Models\SupplierDebtTransaction;
use App\Modules\Purchase\Models\SupplierGroup;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Purchase\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    // ── CRUD ──

    public function list(array $filters, int $perPage = 20)
    {
        return Supplier::with(['group:id,name'])
            ->filter($filters)
            ->paginate($perPage);
    }

    public function options()
    {
        return Supplier::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone', 'group_id', 'debt']);
    }

    public function find(int $id): Supplier
    {
        return Supplier::with(['group:id,name', 'organization:id,name'])->findOrFail($id);
    }

    public function store(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh(['group:id,name']);
    }

    public function destroy(Supplier $supplier): bool
    {
        if ($supplier->purchaseOrders()->where('status', '!=', 'cancelled')->exists()) {
            throw new \Exception('Không thể xóa NCC đang có phiếu nhập.');
        }

        // Đánh dấu DEL
        $supplier->update(['name' => $supplier->name . ' {DEL}', 'status' => 'deleted']);

        return true;
    }

    public function bulkDestroy(array $ids): int
    {
        $count = 0;
        foreach (Supplier::whereIn('id', $ids)->get() as $supplier) {
            try {
                $this->destroy($supplier);
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Ngừng / cho phép hoạt động.
     */
    public function toggleStatus(Supplier $supplier): Supplier
    {
        $supplier->update([
            'status' => $supplier->status === 'active' ? 'inactive' : 'active',
        ]);

        return $supplier->fresh();
    }

    // ── Lịch sử giao dịch ──

    public function transactionHistory(int $supplierId, array $filters = [], int $perPage = 20)
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplierId)
            ->select('id', 'code', DB::raw("'purchase_order' as type"), 'total_amount as amount', 'status', 'order_date as transaction_date', 'created_by')
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('order_date', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('order_date', '<=', $v));

        $purchaseReturns = PurchaseReturn::where('supplier_id', $supplierId)
            ->select('id', 'code', DB::raw("'purchase_return' as type"), 'total_amount as amount', 'status', 'return_date as transaction_date', 'created_by')
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '<=', $v));

        return $purchaseOrders->union($purchaseReturns)
            ->orderByDesc('transaction_date')
            ->paginate($perPage);
    }

    // ── Công nợ ──

    /**
     * Thanh toán công nợ NCC.
     */
    public function payDebt(Supplier $supplier, array $data): SupplierDebtTransaction
    {
        return DB::transaction(function () use ($supplier, $data) {
            $amount = (float) $data['amount'];
            $debtBefore = (float) $supplier->debt;
            $debtAfter = $debtBefore - $amount;

            $tx = SupplierDebtTransaction::create([
                'supplier_id' => $supplier->id,
                'type' => 'payment',
                'amount' => $amount,
                'debt_before' => $debtBefore,
                'debt_after' => max(0, $debtAfter),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            $supplier->update(['debt' => max(0, $debtAfter)]);

            return $tx;
        });
    }

    /**
     * Chiết khấu thanh toán (giảm công nợ).
     */
    public function applyDiscount(Supplier $supplier, array $data): SupplierDebtTransaction
    {
        return DB::transaction(function () use ($supplier, $data) {
            $amount = (float) $data['amount'];
            $debtBefore = (float) $supplier->debt;
            $debtAfter = $debtBefore - $amount;

            $tx = SupplierDebtTransaction::create([
                'supplier_id' => $supplier->id,
                'type' => 'discount',
                'amount' => $amount,
                'debt_before' => $debtBefore,
                'debt_after' => max(0, $debtAfter),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            $supplier->update(['debt' => max(0, $debtAfter)]);

            return $tx;
        });
    }

    /**
     * Điều chỉnh công nợ thủ công.
     */
    public function adjustDebt(Supplier $supplier, array $data): SupplierDebtTransaction
    {
        return DB::transaction(function () use ($supplier, $data) {
            $newDebt = (float) $data['new_debt'];
            $debtBefore = (float) $supplier->debt;

            $tx = SupplierDebtTransaction::create([
                'supplier_id' => $supplier->id,
                'type' => 'adjustment',
                'amount' => abs($newDebt - $debtBefore),
                'debt_before' => $debtBefore,
                'debt_after' => $newDebt,
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            $supplier->update(['debt' => $newDebt]);

            return $tx;
        });
    }

    /**
     * Lịch sử giao dịch công nợ.
     */
    public function debtHistory(int $supplierId, array $filters = [], int $perPage = 20)
    {
        return SupplierDebtTransaction::with(['purchaseOrder:id,code', 'creator:id,name'])
            ->where('supplier_id', $supplierId)
            ->filter($filters)
            ->paginate($perPage);
    }

    // ── Nhóm NCC ──

    public function listGroups()
    {
        return SupplierGroup::withCount('suppliers')->orderBy('name')->get();
    }

    public function storeGroup(array $data): SupplierGroup
    {
        return SupplierGroup::create($data);
    }

    public function updateGroup(int $id, array $data): SupplierGroup
    {
        $group = SupplierGroup::findOrFail($id);
        $group->update($data);

        return $group->fresh();
    }

    public function destroyGroup(int $id): bool
    {
        $group = SupplierGroup::findOrFail($id);
        // Detach suppliers (set group_id = null)
        Supplier::where('group_id', $id)->update(['group_id' => null]);

        return $group->delete();
    }
}
