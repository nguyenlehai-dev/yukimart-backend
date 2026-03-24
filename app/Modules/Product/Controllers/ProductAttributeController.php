<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreProductAttributeRequest;
use App\Modules\Product\Services\ProductAttributeService;

/**
 * @group Thuộc tính sản phẩm
 *
 * Quản lý thuộc tính sản phẩm (Size, Màu sắc, Chất liệu...).
 */
class ProductAttributeController extends Controller
{
    public function __construct(private ProductAttributeService $service) {}

    /** Danh sách thuộc tính kèm giá trị */
    public function index()
    {
        return $this->success($this->service->list());
    }

    /** Tạo thuộc tính mới (kèm values) */
    public function store(StoreProductAttributeRequest $request)
    {
        $attribute = $this->service->store($request->validated());

        return $this->success($attribute, 'Tạo thuộc tính thành công.');
    }

    /** Cập nhật thuộc tính (kèm values) */
    public function update(StoreProductAttributeRequest $request, int $id)
    {
        $attribute = \App\Modules\Product\Models\ProductAttribute::findOrFail($id);
        $attribute = $this->service->update($attribute, $request->validated());

        return $this->success($attribute, 'Cập nhật thuộc tính thành công.');
    }

    /** Xóa thuộc tính */
    public function destroy(int $id)
    {
        $attribute = \App\Modules\Product\Models\ProductAttribute::findOrFail($id);
        $this->service->destroy($attribute);

        return $this->success(null, 'Đã xóa thuộc tính.');
    }
}
