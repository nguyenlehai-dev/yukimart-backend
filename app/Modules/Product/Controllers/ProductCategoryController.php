<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreProductCategoryRequest;
use App\Modules\Product\Requests\UpdateProductCategoryRequest;
use App\Modules\Product\Resources\ProductCategoryCollection;
use App\Modules\Product\Resources\ProductCategoryResource;
use App\Modules\Product\Services\ProductCategoryService;

/**
 * @group Nhóm hàng
 *
 * Quản lý nhóm hàng (danh mục sản phẩm) phân cấp.
 */
class ProductCategoryController extends Controller
{
    public function __construct(private ProductCategoryService $service) {}

    /** Danh sách nhóm hàng (phân trang) */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection(new ProductCategoryCollection($data));
    }

    /** Cây nhóm hàng phân cấp */
    public function tree()
    {
        return $this->success($this->service->tree());
    }

    /** Dropdown options */
    public function options()
    {
        return $this->success($this->service->options());
    }

    /** Chi tiết nhóm hàng */
    public function show(int $id)
    {
        $category = $this->service->find($id);

        return $this->successResource(new ProductCategoryResource($category));
    }

    /** Tạo nhóm hàng mới */
    public function store(StoreProductCategoryRequest $request)
    {
        $category = $this->service->store($request->validated());

        return $this->successResource(new ProductCategoryResource($category), 'Tạo nhóm hàng thành công.', 201);
    }

    /** Cập nhật nhóm hàng */
    public function update(UpdateProductCategoryRequest $request, int $id)
    {
        $category = $this->service->find($id);
        $category = $this->service->update($category, $request->validated());

        return $this->successResource(new ProductCategoryResource($category), 'Cập nhật nhóm hàng thành công.');
    }

    /** Xóa nhóm hàng */
    public function destroy(int $id)
    {
        $category = $this->service->find($id);

        try {
            $this->service->destroy($category);

            return $this->success(null, 'Đã xóa nhóm hàng.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
