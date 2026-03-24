<?php

namespace App\Modules\Document;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Core\Resources\PublicOptionResource;
use App\Modules\Document\Models\DocumentType;
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
 * @group Document - Loại văn bản
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý danh mục loại văn bản: thống kê, danh sách, chi tiết, tạo, cập nhật, xóa, thao tác hàng loạt, xuất/nhập và đổi trạng thái.
 */
class DocumentTypeController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    /**
     * Danh sách loại văn bản công khai
     *
     * Trả về danh sách loại văn bản đang hoạt động để hiển thị cho các chức năng công khai.
     *
     * @unauthenticated
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam sort_by string Sắp xếp theo: id, name, created_at, updated_at. Example: name
     * @queryParam sort_order string Thứ tự: asc, desc. Example: asc
     *
     * @apiResourceCollection App\Modules\Document\Resources\CatalogCollection
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true
     */
    public function public(FilterRequest $request)
    {
        $items = $this->catalogService->publicCatalog(DocumentType::class, $request->all());

        return $this->successCollection(new CatalogCollection($items));
    }

    /**
     * Danh sách loại văn bản công khai cho dropdown
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
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true
     */
    public function publicOptions(FilterRequest $request)
    {
        $items = $this->catalogService->publicOptions(DocumentType::class, $request->all());

        return $this->successCollection(PublicOptionResource::collection($items));
    }

    /**
     * Thống kê loại văn bản
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
        return $this->success($this->catalogService->stats(DocumentType::class, $request->all()));
    }

    /**
     * Danh sách loại văn bản
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
     * @apiResourceModel App\Modules\Document\Models\DocumentType paginate=10
     *
     * @apiResourceAdditional success=true
     */
    public function index(FilterRequest $request)
    {
        $items = $this->catalogService->index(DocumentType::class, $request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new CatalogCollection($items));
    }

    /**
     * Chi tiết loại văn bản
     *
     * @urlParam documentType integer required ID loại văn bản. Example: 1
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true
     */
    public function show(DocumentType $documentType)
    {
        return $this->successResource(new CatalogResource($this->catalogService->show($documentType)));
    }

    /**
     * Tạo loại văn bản
     *
     * @bodyParam name string required Tên loại văn bản. Example: Nghị định
     * @bodyParam description string Mô tả.
     * @bodyParam status string required Trạng thái: active, inactive. Example: active
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource status=201
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true message="Tạo loại văn bản thành công!"
     */
    public function store(StoreCatalogRequest $request)
    {
        $item = $this->catalogService->store(DocumentType::class, $request->validated());

        return $this->successResource(new CatalogResource($item), 'Tạo loại văn bản thành công!', 201);
    }

    /**
     * Cập nhật loại văn bản
     *
     * @urlParam documentType integer required ID loại văn bản. Example: 1
     *
     * @bodyParam name string Tên loại văn bản.
     * @bodyParam description string Mô tả.
     * @bodyParam status string Trạng thái: active, inactive.
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true message="Cập nhật loại văn bản thành công!"
     */
    public function update(UpdateCatalogRequest $request, DocumentType $documentType)
    {
        $item = $this->catalogService->update($documentType, $request->validated());

        return $this->successResource(new CatalogResource($item), 'Cập nhật loại văn bản thành công!');
    }

    /**
     * Xóa loại văn bản
     *
     * @urlParam documentType integer required ID loại văn bản. Example: 1
     *
     * @response 200 {"success": true, "message": "Xóa loại văn bản thành công!"}
     */
    public function destroy(DocumentType $documentType)
    {
        $this->catalogService->destroy($documentType);

        return $this->success(null, 'Xóa loại văn bản thành công!');
    }

    /**
     * Xóa hàng loạt loại văn bản
     *
     * @bodyParam ids array required Danh sách ID. Example: [1,2,3]
     *
     * @response 200 {"success": true, "message": "Xóa hàng loạt thành công!"}
     */
    public function bulkDestroy(BulkDestroyCatalogRequest $request)
    {
        $this->catalogService->bulkDestroy(DocumentType::class, $request->ids);

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    /**
     * Cập nhật trạng thái hàng loạt loại văn bản
     *
     * @bodyParam ids array required Danh sách ID. Example: [1,2,3]
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: inactive
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái hàng loạt thành công!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusCatalogRequest $request)
    {
        $this->catalogService->bulkUpdateStatus(DocumentType::class, $request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    /**
     * Đổi trạng thái loại văn bản
     *
     * @urlParam documentType integer required ID loại văn bản. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: active
     *
     * @apiResource App\Modules\Document\Resources\CatalogResource
     *
     * @apiResourceModel App\Modules\Document\Models\DocumentType
     *
     * @apiResourceAdditional success=true message="Đổi trạng thái thành công!"
     */
    public function changeStatus(ChangeStatusCatalogRequest $request, DocumentType $documentType)
    {
        $item = $this->catalogService->changeStatus($documentType, $request->status);

        return $this->successResource(new CatalogResource($item), 'Đổi trạng thái thành công!');
    }

    /**
     * Xuất Excel loại văn bản
     *
     * Xuất ra các trường: id, name, description, status, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Từ khóa tìm kiếm theo tên.
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     */
    public function export(FilterRequest $request)
    {
        return $this->catalogService->export(DocumentType::class, $request->all(), 'document-types.xlsx');
    }

    /**
     * Import loại văn bản
     *
     * Cột bắt buộc: name. Cột không bắt buộc: description, status (mặc định "active").
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import loại văn bản thành công."}
     */
    public function import(ImportCatalogRequest $request)
    {
        $this->catalogService->import(DocumentType::class, $request->file('file'));

        return $this->success(null, 'Import loại văn bản thành công.');
    }
}
