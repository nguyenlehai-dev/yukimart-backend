<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Exports\StockDisposalExport;
use App\Modules\Inventory\Services\StockDisposalService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockDisposalController extends Controller
{
    public function __construct(
        private StockDisposalService $service,
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
            'status' => 'nullable|in:draft,completed',
            'note' => 'nullable|string',
            'disposal_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.reason' => 'nullable|string',
        ]);

        $disposal = $this->service->store($request->all());

        return $this->success($disposal, 'Tao phieu xuat huy thanh cong.', 201);
    }

    public function update(int $id)
    {
        $disposal = $this->service->find($id);
        $disposal = $this->service->update($disposal, request()->only(['note', 'disposal_date']));

        return $this->success($disposal, 'Cap nhat thanh cong.');
    }

    public function cancel(int $id)
    {
        try {
            $disposal = $this->service->cancel($this->service->find($id));

            return $this->success($disposal, 'Da huy phieu xuat huy.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function complete(int $id)
    {
        try {
            $disposal = $this->service->complete($this->service->find($id));

            return $this->success($disposal, 'Da hoan thanh phieu xuat huy.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function copy(int $id)
    {
        $disposal = $this->service->copy($this->service->find($id));

        return $this->success($disposal, 'Sao chep thanh cong.', 201);
    }

    public function export()
    {
        return Excel::download(new StockDisposalExport(request()->all()), 'xuat-huy.xlsx');
    }
}
