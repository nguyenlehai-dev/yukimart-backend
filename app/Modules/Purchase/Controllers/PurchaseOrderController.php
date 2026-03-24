<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Exports\PurchaseOrderExport;
use App\Modules\Purchase\Requests\StorePurchaseOrderRequest;
use App\Modules\Purchase\Resources\PurchaseOrderResource;
use App\Modules\Purchase\Services\PurchaseOrderService;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderService $service,
    ) {}

    /** Danh sach phieu nhap hang */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection($data);
    }

    /** Chi tiet phieu nhap */
    public function show(int $id)
    {
        return $this->successResource(new PurchaseOrderResource($this->service->find($id)));
    }

    /** Tao phieu nhap hang */
    public function store(StorePurchaseOrderRequest $request)
    {
        $order = $this->service->store($request->validated());

        return $this->successResource(new PurchaseOrderResource($order), 'Tao phieu nhap thanh cong.', 201);
    }

    /** Cap nhat thong tin phieu (NCC, ghi chu, ngay) */
    public function update(int $id)
    {
        $order = $this->service->find($id);
        $order = $this->service->update($order, request()->only(['supplier_id', 'note', 'order_date']));

        return $this->successResource(new PurchaseOrderResource($order), 'Cap nhat thanh cong.');
    }

    /** Mo phieu (completed -> draft, tru ton kho, hoan cong no) */
    public function reopen(int $id)
    {
        $order = $this->service->find($id);

        try {
            $order = $this->service->reopen($order);

            return $this->successResource(new PurchaseOrderResource($order), 'Da mo phieu.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Hoan thanh phieu tam */
    public function complete(int $id)
    {
        $order = $this->service->find($id);

        try {
            $order = $this->service->complete($order);

            return $this->successResource(new PurchaseOrderResource($order), 'Da hoan thanh phieu nhap.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Huy phieu nhap */
    public function cancel(int $id)
    {
        $order = $this->service->find($id);

        try {
            $order = $this->service->cancel($order);

            return $this->successResource(new PurchaseOrderResource($order), 'Da huy phieu nhap.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Sao chep phieu nhap */
    public function copy(int $id)
    {
        $order = $this->service->find($id);
        $newOrder = $this->service->copy($order);

        return $this->successResource(new PurchaseOrderResource($newOrder), 'Sao chep thanh cong.', 201);
    }

    /** Xuat Excel */
    public function export()
    {
        return Excel::download(new PurchaseOrderExport(request()->all()), 'nhap-hang.xlsx');
    }
}
