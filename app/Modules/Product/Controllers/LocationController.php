<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Requests\StoreLocationRequest;
use App\Modules\Product\Resources\LocationResource;
use App\Modules\Product\Services\LocationService;

/**
 * @group Vị trí
 *
 * Quản lý vị trí lưu trữ/trưng bày sản phẩm.
 */
class LocationController extends Controller
{
    public function __construct(private LocationService $service) {}

    /** Danh sách vị trí */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection(LocationResource::collection($data));
    }

    /** Dropdown options */
    public function options()
    {
        return $this->success($this->service->options());
    }

    /** Tạo vị trí */
    public function store(StoreLocationRequest $request)
    {
        $location = $this->service->store($request->validated());

        return $this->successResource(new LocationResource($location), 'Tạo vị trí thành công.', 201);
    }

    /** Cập nhật vị trí */
    public function update(StoreLocationRequest $request, int $id)
    {
        $location = $this->service->find($id);
        $location = $this->service->update($location, $request->validated());

        return $this->successResource(new LocationResource($location), 'Cập nhật vị trí thành công.');
    }

    /** Xóa vị trí */
    public function destroy(int $id)
    {
        $location = $this->service->find($id);

        try {
            $this->service->destroy($location);

            return $this->success(null, 'Đã xóa vị trí.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
