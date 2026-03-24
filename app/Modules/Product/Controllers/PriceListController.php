<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Exports\PriceListExport;
use App\Modules\Product\Requests\StorePriceListRequest;
use App\Modules\Product\Requests\UpdatePriceListRequest;
use App\Modules\Product\Requests\UpsertPriceListItemsRequest;
use App\Modules\Product\Resources\PriceListResource;
use App\Modules\Product\Services\PriceListService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @group Thiết lập giá
 *
 * Quản lý bảng giá: CRUD, thêm sản phẩm, công thức, so sánh.
 */
class PriceListController extends Controller
{
    public function __construct(
        private PriceListService $service,
    ) {}

    /** Danh sách bảng giá */
    public function index()
    {
        $data = $this->service->list(request()->all());

        return $this->successCollection($data);
    }

    /** Chi tiết bảng giá (kèm items, organizations) */
    public function show(int $id)
    {
        $priceList = $this->service->find($id);

        return $this->successResource(new PriceListResource($priceList));
    }

    /** Tạo bảng giá mới */
    public function store(StorePriceListRequest $request)
    {
        $priceList = $this->service->store($request->validated());

        return $this->successResource(new PriceListResource($priceList), 'Tạo bảng giá thành công.', 201);
    }

    /** Cập nhật bảng giá */
    public function update(UpdatePriceListRequest $request, int $id)
    {
        $priceList = $this->service->find($id);
        $priceList = $this->service->update($priceList, $request->validated());

        return $this->successResource(new PriceListResource($priceList), 'Cập nhật bảng giá thành công.');
    }

    /** Xóa bảng giá */
    public function destroy(int $id)
    {
        $priceList = $this->service->find($id);

        try {
            $this->service->destroy($priceList);

            return $this->success(null, 'Đã xóa bảng giá.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // ── Items ──

    /** Thêm/cập nhật sản phẩm vào bảng giá */
    public function upsertItems(UpsertPriceListItemsRequest $request, int $id)
    {
        $priceList = $this->service->find($id);
        $priceList = $this->service->upsertItems($priceList, $request->validated('items'));

        return $this->successResource(new PriceListResource($priceList), 'Cập nhật giá thành công.');
    }

    /** Xóa sản phẩm khỏi bảng giá */
    public function removeItems(int $id)
    {
        $priceList = $this->service->find($id);
        $count = $this->service->removeItems($priceList, request('item_ids', []));

        return $this->success(null, "Đã xóa {$count} sản phẩm khỏi bảng giá.");
    }

    /** Thêm tất cả sản phẩm vào bảng giá */
    public function addAllProducts(int $id)
    {
        $priceList = $this->service->find($id);
        $count = $this->service->addAllProducts($priceList);

        return $this->success(null, "Đã thêm {$count} sản phẩm vào bảng giá.");
    }

    /** Thêm sản phẩm theo nhóm hàng */
    public function addByCategory(int $id)
    {
        $priceList = $this->service->find($id);
        $count = $this->service->addByCategory($priceList, request('category_id'));

        return $this->success(null, "Đã thêm {$count} sản phẩm từ nhóm hàng.");
    }

    /** Áp dụng công thức cho tất cả sản phẩm */
    public function applyFormula(int $id)
    {
        $priceList = $this->service->find($id);
        $count = $this->service->applyFormulaToAll(
            $priceList,
            request('formula_type'),
            (float) request('formula_value'),
            request('base_price_list_id')
        );

        return $this->success(null, "Đã áp dụng công thức cho {$count} sản phẩm.");
    }

    // ── So sánh & Export ──

    /** So sánh nhiều bảng giá (tối đa 5) */
    public function compare()
    {
        $priceListIds = request('price_list_ids', []);
        if (count($priceListIds) > 5) {
            return $this->error('Chỉ được so sánh tối đa 5 bảng giá.', 422);
        }

        $data = $this->service->compare($priceListIds, request()->all());

        return $this->success($data);
    }

    /** Xuất Excel bảng giá */
    public function export(int $id)
    {
        $priceList = $this->service->find($id);

        return Excel::download(new PriceListExport($priceList), "bang-gia-{$priceList->slug}.xlsx");
    }
}
