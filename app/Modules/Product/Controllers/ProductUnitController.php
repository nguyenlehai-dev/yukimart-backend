<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreProductUnitRequest;
use App\Modules\Product\Services\ProductUnitService;

/**
 * @group Đơn vị tính
 *
 * Quản lý đơn vị tính sản phẩm (cái, chai, thùng, kg...).
 */
class ProductUnitController extends Controller
{
    public function __construct(private ProductUnitService $service) {}

    /** Danh sách đơn vị tính */
    public function index()
    {
        $data = $this->service->list();

        return $this->success($data);
    }

    /** Dropdown options */
    public function options()
    {
        return $this->success($this->service->options());
    }

    /** Tạo đơn vị tính */
    public function store(StoreProductUnitRequest $request)
    {
        $unit = $this->service->store($request->validated());

        return $this->success($unit, 'Tạo đơn vị tính thành công.');
    }

    /** Cập nhật đơn vị tính */
    public function update(StoreProductUnitRequest $request, int $id)
    {
        $unit = \App\Modules\Product\Models\ProductUnit::findOrFail($id);
        $unit = $this->service->update($unit, $request->validated());

        return $this->success($unit, 'Cập nhật đơn vị tính thành công.');
    }

    /** Xóa đơn vị tính */
    public function destroy(int $id)
    {
        $unit = \App\Modules\Product\Models\ProductUnit::findOrFail($id);
        $this->service->destroy($unit);

        return $this->success(null, 'Đã xóa đơn vị tính.');
    }
}
