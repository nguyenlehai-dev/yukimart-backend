<?php

namespace App\Modules\Purchase\Services;

use App\Modules\Purchase\Models\Supplier;

class SupplierService
{
    public function list(array $filters, int $perPage = 20)
    {
        return Supplier::filter($filters)->paginate($perPage);
    }

    public function options()
    {
        return Supplier::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone']);
    }

    public function find(int $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    public function store(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->fresh();
    }

    public function destroy(Supplier $supplier): bool
    {
        if ($supplier->purchaseOrders()->exists()) {
            throw new \Exception('Không thể xóa NCC đang có phiếu nhập.');
        }

        return $supplier->delete();
    }
}
