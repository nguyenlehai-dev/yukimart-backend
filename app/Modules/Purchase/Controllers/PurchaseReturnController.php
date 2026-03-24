<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Exports\PurchaseReturnExport;
use App\Modules\Purchase\Requests\StorePurchaseReturnRequest;
use App\Modules\Purchase\Resources\PurchaseReturnResource;
use App\Modules\Purchase\Services\PurchaseReturnService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @group Tra hang nhap
 *
 * Quan ly phieu tra hang nhap: tra nhanh, tra theo phieu nhap, huy, sao chep.
 */
class PurchaseReturnController extends Controller
{
    public function __construct(
        private PurchaseReturnService $service,
    ) {}

    /** Danh sach phieu tra hang nhap */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection($data);
    }

    /** Chi tiet phieu tra hang nhap */
    public function show(int $id)
    {
        $return = $this->service->find($id);

        return $this->successResource(new PurchaseReturnResource($return));
    }

    /** Tra hang nhap nhanh (khong theo phieu nhap) */
    public function storeQuick(StorePurchaseReturnRequest $request)
    {
        $return = $this->service->storeQuick($request->validated());

        return $this->successResource(new PurchaseReturnResource($return), 'Tra hang nhap thanh cong.', 201);
    }

    /** Tra hang nhap theo phieu nhap hang */
    public function storeFromOrder(StorePurchaseReturnRequest $request)
    {
        $return = $this->service->storeFromPurchaseOrder($request->validated());

        return $this->successResource(new PurchaseReturnResource($return), 'Tra hang nhap theo phieu thanh cong.', 201);
    }

    /** Cap nhat thong tin phieu (ghi chu, ngay tra) */
    public function update(int $id)
    {
        $return = $this->service->find($id);
        $return = $this->service->update($return, request()->only(['note', 'return_date']));

        return $this->successResource(new PurchaseReturnResource($return), 'Cap nhat thanh cong.');
    }

    /** Huy phieu tra hang nhap */
    public function cancel(int $id)
    {
        $return = $this->service->find($id);

        try {
            $return = $this->service->cancel($return);

            return $this->successResource(new PurchaseReturnResource($return), 'Da huy phieu tra hang nhap.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Sao chep phieu tra hang nhap */
    public function copy(int $id)
    {
        $return = $this->service->find($id);
        $newReturn = $this->service->copy($return);

        return $this->successResource(new PurchaseReturnResource($newReturn), 'Sao chep thanh cong.', 201);
    }

    /** Xuat Excel danh sach phieu */
    public function export()
    {
        return Excel::download(new PurchaseReturnExport(request()->all()), 'tra-hang-nhap.xlsx');
    }
}
