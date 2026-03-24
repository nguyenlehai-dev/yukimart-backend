<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Exports\StockCheckExport;
use App\Modules\Inventory\Services\StockCheckService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockCheckController extends Controller
{
    public function __construct(
        private StockCheckService $service,
    ) {}

    public function index()
    {
        return $this->successCollection($this->service->list(request()->all()));
    }

    public function show(int $id)
    {
        return $this->success($this->service->find($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'status' => 'nullable|in:draft,balanced',
            'note' => 'nullable|string',
            'check_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        $check = $this->service->store($request->all());

        return $this->success($check, 'Tao phieu kiem kho thanh cong.', 201);
    }

    public function update(int $id, Request $request)
    {
        try {
            $check = $this->service->updateDraft($this->service->find($id), $request->all());

            return $this->success($check, 'Cap nhat thanh cong.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Hoan thanh + Can bang kho */
    public function balance(int $id)
    {
        try {
            $check = $this->service->balance($this->service->find($id));

            return $this->success($check, 'Da can bang kho thanh cong.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function cancel(int $id)
    {
        try {
            $check = $this->service->cancel($this->service->find($id));

            return $this->success($check, 'Da huy phieu kiem kho.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function copy(int $id)
    {
        $check = $this->service->copy($this->service->find($id));

        return $this->success($check, 'Sao chep thanh cong.', 201);
    }

    /** Gop nhieu phieu tam */
    public function merge(Request $request)
    {
        $request->validate(['check_ids' => 'required|array|min:2']);

        try {
            $check = $this->service->merge($request->input('check_ids'));

            return $this->success($check, 'Gop phieu thanh cong.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function export()
    {
        return Excel::download(new StockCheckExport(request()->all()), 'kiem-kho.xlsx');
    }
}
