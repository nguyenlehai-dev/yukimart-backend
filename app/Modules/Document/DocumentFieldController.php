<?php

namespace App\Modules\Document;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Core\Resources\PublicOptionResource;
use App\Modules\Document\Models\DocumentField;
use App\Modules\Document\Requests\BulkDestroyCatalogRequest;
use App\Modules\Document\Requests\BulkUpdateStatusCatalogRequest;
use App\Modules\Document\Requests\ChangeStatusCatalogRequest;
use App\Modules\Document\Requests\ImportCatalogRequest;
use App\Modules\Document\Requests\StoreCatalogRequest;
use App\Modules\Document\Requests\UpdateCatalogRequest;
use App\Modules\Document\Resources\CatalogCollection;
use App\Modules\Document\Resources\CatalogResource;
use App\Modules\Document\Services\CatalogService;

/**
 * @group Document - Lĩnh vực
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý danh mục lĩnh vực: thống kê, danh sách, chi tiết, tạo, cập nhật, xóa, thao tác hàng loạt, xuất/nhập và đổi trạng thái.
 */
class DocumentFieldController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    /**
     * Danh sách lĩnh vực công khai
     *
     * Trả về danh sách lĩnh vực đang hoạt động để hiển thị cho các chức năng công khai.
     *
     * @unauthenticated
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam sort_by string Sắp xếp theo: id, name, created_at, updated_at. Example: name
     * @queryParam sort_order string Thứ tự: asc, desc. Example: asc
     *
     * @apiResourceCollection App\Modules\Document\Resources\CatalogCollection
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true
     */
    public function public(FilterRequest $request)
    {
        $items = $this->catalogService->publicCatalog(DocumentField::class, $request->all());

        return $this->successCollection(new CatalogCollection($items));
    }

    /**
     * Danh sách lĩnh vực công khai cho dropdown
     *
     * Trả về dữ liệu tối giản chỉ gồm id, name, description để tối ưu payload cho dropdown.
     *
     * @unauthenticated
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam sort_by string Sắp xếp theo: id, name, created_at, updated_at. Example: name
     * @queryParam sort_order string Thứ tự: asc, desc. Example: asc
     *
     * @apiResourceCollection App\Modules\Core\Resources\PublicOptionResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true
     */
    public function publicOptions(FilterRequest $request)
    {
        $items = $this->catalogService->publicOptions(DocumentField::class, $request->all());

        return $this->successCollection(PublicOptionResource::collection($items));
    }

    /**
     * Thống kê lĩnh vực
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     * @queryParam from_date date Lọc từ ngày tạo (Y-m-d). Example: 2026-01-01
     * @queryParam to_date date Lọc đến ngày tạo (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sắp xếp theo: id, name, created_at, updated_at. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @response 200 {"success": true, "data": {"total": 10, "active": 8, "inactive": 2}}
     */
    public function stats(FilterRequest $request)
    {
        return $this->success($this->catalogService->stats(DocumentField::class, $request->all()));
    }

    /**
     * Danh sách lĩnh vực
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     * @queryParam from_date date Lọc từ ngày tạo (Y-m-d). Example: 2026-01-01
     * @queryParam to_date date Lọc đến ngày tạo (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sắp xếp theo: id, name, created_at, updated_at. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @apiResourceCollection App\Modules\Document\Resources\CatalogCollection
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField paginate=10
     *
     * @apiResourceAdditional success=true
     */
    public function index(FilterRequest $request)
    {
        $items = $this->catalogService->index(DocumentField::class, $request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new CatalogCollection($items));
    }

    /**
     * Chi tiết lĩnh vực
     *
     * @urlParam documentField integer required ID lĩnh vực. Example: 1
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true
     */
    public function show(DocumentField $documentField)
    {
        return $this->successResource(new CatalogResource($this->catalogService->show($documentField)));
    }

    /**
     * Tạo lĩnh vực
     *
     * @bodyParam name string required Tên lĩnh vực. Example: Tài chính
     * @bodyParam description string Mô tả.
     * @bodyParam status string required Trạng thái: active, inactive. Example: active
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource status=201
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true message="Tạo lĩnh vực thành công!"
     */
    public function store(StoreCatalogRequest $request)
    {
        $item = $this->catalogService->store(DocumentField::class, $request->validated());

        return $this->successResource(new CatalogResource($item), 'Tạo lĩnh vực thành công!', 201);
    }

    /**
     * Cập nhật lĩnh vực
     *
     * @urlParam documentField integer required ID lĩnh vực. Example: 1
     *
     * @bodyParam name string Tên lĩnh vực.
     * @bodyParam description string Mô tả.
     * @bodyParam status string Trạng thái: active, inactive.
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true message="Cập nhật lĩnh vực thành công!"
     */
    public function update(UpdateCatalogRequest $request, DocumentField $documentField)
    {
        $item = $this->catalogService->update($documentField, $request->validated());

        return $this->successResource(new CatalogResource($item), 'Cập nhật lĩnh vực thành công!');
    }

    /**
     * Xóa lĩnh vực
     *
     * @urlParam documentField integer required ID lĩnh vực. Example: 1
     *
     * @response 200 {"success": true, "message": "Xóa lĩnh vực thành công!"}
     */
    public function destroy(DocumentField $documentField)
    {
        $this->catalogService->destroy($documentField);

        return $this->success(null, 'Xóa lĩnh vực thành công!');
    }

    /**
     * Xóa hàng loạt lĩnh vực
     *
     * @bodyParam ids array required Danh sách ID. Example: [1,2,3]
     *
     * @response 200 {"success": true, "message": "Xóa hàng loạt thành công!"}
     */
    public function bulkDestroy(BulkDestroyCatalogRequest $request)
    {
        $this->catalogService->bulkDestroy(DocumentField::class, $request->ids);

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    /**
     * Cập nhật trạng thái hàng loạt lĩnh vực
     *
     * @bodyParam ids array required Danh sách ID. Example: [1,2,3]
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: inactive
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái hàng loạt thành công!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusCatalogRequest $request)
    {
        $this->catalogService->bulkUpdateStatus(DocumentField::class, $request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    /**
     * Đổi trạng thái lĩnh vực
     *
     * @urlParam documentField integer required ID lĩnh vực. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: active
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentField
     *
     * @apiResourceAdditional success=true message="Đổi trạng thái thành công!"
     */
    public function changeStatus(ChangeStatusCatalogRequest $request, DocumentField $documentField)
    {
        $item = $this->catalogService->changeStatus($documentField, $request->status);

        return $this->successResource(new CatalogResource($item), 'Đổi trạng thái thành công!');
    }

    /**
     * Xuất Excel lĩnh vực
     *
     * Xuất ra các trường: id, name, description, status, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     */
    public function export(FilterRequest $request)
    {
        return $this->catalogService->export(DocumentField::class, $request->all(), 'document-fields.xlsx');
    }

    /**
     * Import lĩnh vực
     *
     * Cột bắt buộc: name. Cột không bắt buộc: description, status (mặc định "active").
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import lĩnh vực thành công."}
     */
    public function import(ImportCatalogRequest $request)
    {
        $this->catalogService->import(DocumentField::class, $request->file('file'));

        return $this->success(null, 'Import lĩnh vực thành công.');
    }
}
