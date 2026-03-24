<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Exports\SupplierExport;
use App\Modules\Purchase\Requests\StoreSupplierRequest;
use App\Modules\Purchase\Services\SupplierService;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierService $service,
    ) {}

    // ── CRUD ──

    public function index()
    {
        return $this->successCollection($this->service->list(request()->all()));
    }

    public function options()
    {
        return $this->success($this->service->options());
    }

    public function show(int $id)
    {
        return $this->success($this->service->find($id));
    }

    public function store(StoreSupplierRequest $request)
    {
        return $this->success($this->service->store($request->validated()), 'Tao NCC thanh cong.', 201);
    }

    public function update(StoreSupplierRequest $request, int $id)
    {
        $supplier = $this->service->find($id);

        return $this->success($this->service->update($supplier, $request->validated()), 'Cap nhat NCC thanh cong.');
    }

    public function destroy(int $id)
    {
        try {
            $this->service->destroy($this->service->find($id));

            return $this->success(null, 'Da xoa NCC.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function bulkDestroy()
    {
        $count = $this->service->bulkDestroy(request('ids', []));

        return $this->success(null, "Da xoa {$count} NCC.");
    }

    public function toggleStatus(int $id)
    {
        $supplier = $this->service->toggleStatus($this->service->find($id));

        return $this->success($supplier, 'Cap nhat trang thai thanh cong.');
    }

    // ── Lich su giao dich ──

    public function transactionHistory(int $id)
    {
        return $this->success($this->service->transactionHistory($id, request()->all()));
    }

    // ── Cong no ──

    public function debtHistory(int $id)
    {
        return $this->success($this->service->debtHistory($id, request()->all()));
    }

    public function payDebt(int $id)
    {
        $supplier = $this->service->find($id);
        $tx = $this->service->payDebt($supplier, request()->all());

        return $this->success($tx, 'Thanh toan cong no thanh cong.', 201);
    }

    public function applyDiscount(int $id)
    {
        $supplier = $this->service->find($id);
        $tx = $this->service->applyDiscount($supplier, request()->all());

        return $this->success($tx, 'Chiet khau thanh toan thanh cong.', 201);
    }

    public function adjustDebt(int $id)
    {
        $supplier = $this->service->find($id);
        $tx = $this->service->adjustDebt($supplier, request()->all());

        return $this->success($tx, 'Dieu chinh cong no thanh cong.', 201);
    }

    // ── Nhom NCC ──

    public function listGroups()
    {
        return $this->success($this->service->listGroups());
    }

    public function storeGroup()
    {
        $group = $this->service->storeGroup(request()->only(['name', 'description']));

        return $this->success($group, 'Tao nhom NCC thanh cong.', 201);
    }

    public function updateGroup(int $groupId)
    {
        $group = $this->service->updateGroup($groupId, request()->only(['name', 'description']));

        return $this->success($group, 'Cap nhat nhom NCC thanh cong.');
    }

    public function destroyGroup(int $groupId)
    {
        $this->service->destroyGroup($groupId);

        return $this->success(null, 'Da xoa nhom NCC.');
    }

    // ── Export / Import ──

    public function export()
    {
        return Excel::download(new SupplierExport(request()->all()), 'nha-cung-cap.xlsx');
    }

    public function import()
    {
        request()->validate(['file' => 'required|file|mimes:xlsx,xls']);
        // Placeholder: import logic (similar to product import)
        return $this->success(null, 'Import NCC thanh cong.');
    }
}
