<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Requests\StoreSupplierRequest;
use App\Modules\Purchase\Services\SupplierService;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierService $service,
    ) {}

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
        $supplier = $this->service->update($supplier, $request->validated());

        return $this->success($supplier, 'Cap nhat NCC thanh cong.');
    }

    public function destroy(int $id)
    {
        $supplier = $this->service->find($id);

        try {
            $this->service->destroy($supplier);

            return $this->success(null, 'Da xoa NCC.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
