<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreBrandRequest;
use App\Modules\Product\Resources\BrandResource;
use App\Modules\Product\Services\BrandService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Thương hiệu
 *
 * Quản lý thương hiệu sản phẩm.
 */
class BrandController extends Controller
{
    public function __construct(private BrandService $service) {}

    /** Danh sách thương hiệu */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection(BrandResource::collection($data));
    }

    /** Dropdown options */
    public function options()
    {
        return $this->success($this->service->options());
    }

    /** Tạo thương hiệu */
    public function store(StoreBrandRequest $request)
    {
        $brand = $this->service->store($request->validated());

        return $this->successResource(new BrandResource($brand), 'Tạo thương hiệu thành công.', 201);
    }

    /** Cập nhật thương hiệu */
    public function update(StoreBrandRequest $request, int $id)
    {
        $brand = $this->service->find($id);
        $brand = $this->service->update($brand, $request->validated());

        return $this->successResource(new BrandResource($brand), 'Cập nhật thương hiệu thành công.');
    }

    /** Xóa thương hiệu */
    public function destroy(int $id)
    {
        $brand = $this->service->find($id);

        try {
            $this->service->destroy($brand);

            return $this->success(null, 'Đã xóa thương hiệu.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
