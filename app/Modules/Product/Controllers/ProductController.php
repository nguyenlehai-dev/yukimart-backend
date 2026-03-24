<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Exports\ProductExport;
use App\Modules\Product\Imports\ProductImport;
use App\Modules\Product\Requests\BulkActionProductRequest;
use App\Modules\Product\Requests\ImportProductRequest;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductCollection;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\InventoryService;
use App\Modules\Product\Services\ProductService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @group Hàng hóa
 *
 * Quản lý sản phẩm: hàng hóa, dịch vụ, combo, hàng sản xuất.
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductService $service,
        private InventoryService $inventoryService,
    ) {}

    /** Danh sách hàng hóa (filter, search, sort, paginate) */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection(new ProductCollection($data));
    }

    /** Chi tiết hàng hóa (kèm variants, components, images) */
    public function show(int $id)
    {
        $product = $this->service->find($id);

        return $this->successResource(new ProductResource($product));
    }

    /** Tạo hàng hóa mới */
    public function store(StoreProductRequest $request)
    {
        $product = $this->service->store($request->validated());

        return $this->successResource(new ProductResource($product), 'Tạo hàng hóa thành công.', 201);
    }

    /** Cập nhật hàng hóa */
    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->service->find($id);
        $product = $this->service->update($product, $request->validated());

        return $this->successResource(new ProductResource($product), 'Cập nhật hàng hóa thành công.');
    }

    /** Xóa hàng hóa */
    public function destroy(int $id)
    {
        $product = $this->service->find($id);

        try {
            $this->service->destroy($product);

            return $this->success(null, 'Đã xóa hàng hóa.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /** Sao chép hàng hóa */
    public function copy(int $id)
    {
        $product = $this->service->find($id);
        $newProduct = $this->service->copy($product);

        return $this->successResource(new ProductResource($newProduct), 'Sao chép hàng hóa thành công.', 201);
    }

    /** Ngừng / Cho phép kinh doanh */
    public function toggleActive(int $id)
    {
        $product = $this->service->find($id);
        $product = $this->service->toggleActive($product);

        return $this->successResource(new ProductResource($product), 'Đã cập nhật trạng thái kinh doanh.');
    }

    /** Batch: Ngừng/Cho phép kinh doanh hàng loạt */
    public function bulkToggleActive(BulkActionProductRequest $request)
    {
        $count = $this->service->bulkToggleActive(
            $request->validated('ids'),
            $request->boolean('is_active')
        );

        return $this->success(null, "Đã cập nhật {$count} sản phẩm.");
    }

    /** Batch: Đổi nhóm hàng */
    public function bulkCategory(BulkActionProductRequest $request)
    {
        $count = $this->service->bulkCategory(
            $request->validated('ids'),
            $request->validated('category_id')
        );

        return $this->success(null, "Đã đổi nhóm hàng {$count} sản phẩm.");
    }

    /** Batch: Thiết lập điểm */
    public function bulkPoint(BulkActionProductRequest $request)
    {
        $count = $this->service->bulkPoint(
            $request->validated('ids'),
            $request->validated('point')
        );

        return $this->success(null, "Đã thiết lập điểm {$count} sản phẩm.");
    }

    /** Batch: Xóa hàng loạt */
    public function bulkDelete(BulkActionProductRequest $request)
    {
        $count = $this->service->bulkDelete($request->validated('ids'));

        return $this->success(null, "Đã xóa {$count} sản phẩm.");
    }

    // ── Export / Import ──

    /** Xuất Excel */
    public function export()
    {
        return Excel::download(new ProductExport(request()->all()), 'products.xlsx');
    }

    /** Nhập Excel */
    public function import(ImportProductRequest $request)
    {
        $import = new ProductImport();
        Excel::import($import, $request->file('file'));

        return $this->success(null, "Import thành công {$import->getCount()} sản phẩm.");
    }

    // ── Tồn kho & Thẻ kho ──

    /** Tồn kho theo chi nhánh */
    public function inventory(int $id)
    {
        $data = $this->inventoryService->getInventoryByProduct($id);

        return $this->success($data);
    }

    /** Thẻ kho (lịch sử giao dịch) */
    public function stockCard(int $id)
    {
        $data = $this->inventoryService->getStockCard($id, request()->all());

        return $this->success($data);
    }

    /** Phân tích hiệu quả kinh doanh */
    public function analytics(int $id)
    {
        $data = $this->inventoryService->getAnalytics($id, request()->all());

        return $this->success($data);
    }
}
