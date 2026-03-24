<?php

namespace App\Modules\Document;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Requests\BulkDestroyDocumentRequest;
use App\Modules\Document\Requests\BulkUpdateStatusDocumentRequest;
use App\Modules\Document\Requests\ChangeStatusDocumentRequest;
use App\Modules\Document\Requests\ImportDocumentRequest;
use App\Modules\Document\Requests\StoreDocumentRequest;
use App\Modules\Document\Requests\UpdateDocumentRequest;
use App\Modules\Document\Resources\DocumentCollection;
use App\Modules\Document\Resources\DocumentResource;
use App\Modules\Document\Services\DocumentService;

/**
 * @group Document - Văn bản
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý văn bản pháp lý và các tệp đính kèm.
 */
class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    /**
     * Thống kê văn bản
     *
     * Tổng số văn bản, đang hoạt động (active), không hoạt động (inactive). Áp dụng cùng bộ lọc với danh sách.
     *
     * @queryParam search string Tìm theo số ký hiệu hoặc tên văn bản. Example: VB-01
     * @queryParam status string Trạng thái: active, inactive. Example: active
     * @queryParam document_type_id integer Lọc theo loại văn bản. Example: 1
     * @queryParam document_field_id integer Lọc theo lĩnh vực. Example: 2
     * @queryParam issuing_agency_id integer Lọc theo cơ quan ban hành. Example: 1
     * @queryParam issuing_level_id integer Lọc theo cấp ban hành. Example: 1
     * @queryParam signer_id integer Lọc theo người ký. Example: 1
     * @queryParam from_date date Lọc từ ngày tạo (Y-m-d). Example: 2026-01-01
     * @queryParam to_date date Lọc đến ngày tạo (Y-m-d). Example: 2026-12-31
     *
     * @response 200 {"success": true, "data": {"total": 10, "active": 7, "inactive": 3}}
     */
    public function stats(FilterRequest $request)
    {
        return $this->success($this->documentService->stats($request->all()));
    }

    /**
     * Danh sách văn bản
     *
     * @queryParam search string Tìm theo số ký hiệu hoặc tên văn bản.
     * @queryParam status string Trạng thái: active, inactive.
     * @queryParam document_type_id integer Lọc theo loại văn bản.
     * @queryParam document_field_id integer Lọc theo lĩnh vực.
     * @queryParam issuing_agency_id integer Lọc theo cơ quan ban hành.
     * @queryParam issuing_level_id integer Lọc theo cấp ban hành.
     * @queryParam signer_id integer Lọc theo người ký.
     * @queryParam sort_by string Sắp xếp theo id, so_ky_hieu, ten_van_ban, ngay_ban_hanh, created_at, updated_at.
     * @queryParam sort_order string asc|desc.
     * @queryParam limit integer Số lượng mỗi trang.
     *
     * @apiResourceCollection App\Modules\Document\Resources\DocumentCollection
     *
     * @apiResourceModel App\Modules\Document\Models\Document paginate=10
     *
     * @apiResourceAdditional success=true
     */
    public function index(FilterRequest $request)
    {
        $documents = $this->documentService->index($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new DocumentCollection($documents));
    }

    /**
     * Chi tiết văn bản
     *
     * @urlParam document integer required ID văn bản.
     *
     * @apiResource App\Modules\Document\Resources\DocumentResource
     *
     * @apiResourceModel App\Modules\Document\Models\Document with=issuingAgency,issuingLevel,signer,types,fields
     *
     * @apiResourceAdditional success=true
     */
    public function show(Document $document)
    {
        $document = $this->documentService->show($document);

        return $this->successResource(new DocumentResource($document));
    }

    /**
     * Tạo mới văn bản
     *
     * @bodyParam so_ky_hieu string required Số ký hiệu văn bản.
     * @bodyParam ten_van_ban string required Tên văn bản.
     * @bodyParam noi_dung string Nội dung văn bản.
     * @bodyParam issuing_agency_id integer ID cơ quan ban hành.
     * @bodyParam issuing_level_id integer ID cấp ban hành.
     * @bodyParam signer_id integer ID người ký.
     * @bodyParam document_type_ids array Danh sách ID loại văn bản.
     * @bodyParam document_field_ids array Danh sách ID lĩnh vực.
     * @bodyParam ngay_ban_hanh date Ngày ban hành.
     * @bodyParam ngay_xuat_ban date Ngày xuất bản.
     * @bodyParam ngay_hieu_luc date Ngày hiệu lực.
     * @bodyParam ngay_het_hieu_luc date Ngày hết hiệu lực.
     * @bodyParam status string required active|inactive.
     * @bodyParam attachments[] file Nhiều file đính kèm.
     *
     * @apiResource App\Modules\Document\Resources\DocumentResource status=201
     *
     * @apiResourceModel App\Modules\Document\Models\Document with=issuingAgency,issuingLevel,signer,types,fields
     *
     * @apiResourceAdditional success=true message="Tạo văn bản thành công!"
     */
    public function store(StoreDocumentRequest $request)
    {
        $document = $this->documentService->store($request->validated(), $request->file('attachments', []));

        return $this->successResource(new DocumentResource($document), 'Tạo văn bản thành công!', 201);
    }

    /**
     * Cập nhật văn bản
     *
     * @urlParam document integer required ID văn bản.
     *
     * @bodyParam so_ky_hieu string Số ký hiệu văn bản.
     * @bodyParam ten_van_ban string Tên văn bản.
     * @bodyParam noi_dung string Nội dung văn bản.
     * @bodyParam issuing_agency_id integer ID cơ quan ban hành.
     * @bodyParam issuing_level_id integer ID cấp ban hành.
     * @bodyParam signer_id integer ID người ký.
     * @bodyParam document_type_ids array Danh sách ID loại văn bản.
     * @bodyParam document_field_ids array Danh sách ID lĩnh vực.
     * @bodyParam ngay_ban_hanh date Ngày ban hành.
     * @bodyParam ngay_xuat_ban date Ngày xuất bản.
     * @bodyParam ngay_hieu_luc date Ngày hiệu lực.
     * @bodyParam ngay_het_hieu_luc date Ngày hết hiệu lực.
     * @bodyParam status string Trạng thái: active, inactive.
     * @bodyParam attachments[] file Nhiều file đính kèm (append).
     * @bodyParam remove_attachment_ids array Danh sách media id cần xóa.
     *
     * @apiResource App\Modules\Document\Resources\DocumentResource
     *
     * @apiResourceModel App\Modules\Document\Models\Document with=issuingAgency,issuingLevel,signer,types,fields
     *
     * @apiResourceAdditional success=true message="Cập nhật văn bản thành công!"
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $document = $this->documentService->update($document, $request->validated(), $request->file('attachments', []));

        return $this->successResource(new DocumentResource($document), 'Cập nhật văn bản thành công!');
    }

    /**
     * Xóa văn bản
     *
     * @urlParam document integer required ID văn bản. Example: 1
     *
     * @response 200 {"success": true, "message": "Xóa văn bản thành công!"}
     */
    public function destroy(Document $document)
    {
        $this->documentService->destroy($document);

        return $this->success(null, 'Xóa văn bản thành công!');
    }

    /**
     * Xóa hàng loạt văn bản
     *
     * @bodyParam ids array required Danh sách ID văn bản. Example: [1,2,3]
     *
     * @response 200 {"success": true, "message": "Xóa hàng loạt văn bản thành công!"}
     */
    public function bulkDestroy(BulkDestroyDocumentRequest $request)
    {
        $this->documentService->bulkDestroy($request->ids);

        return $this->success(null, 'Xóa hàng loạt văn bản thành công!');
    }

    /**
     * Cập nhật trạng thái hàng loạt
     *
     * @bodyParam ids array required Danh sách ID văn bản. Example: [1,2,3]
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: inactive
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái hàng loạt thành công!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusDocumentRequest $request)
    {
        $this->documentService->bulkUpdateStatus($request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    /**
     * Đổi trạng thái văn bản
     *
     * @urlParam document integer required ID văn bản. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: active
     *
     * @apiResource App\Modules\Document\Resources\DocumentResource
     *
     * @apiResourceModel App\Modules\Document\Models\Document with=issuingAgency,issuingLevel,signer,types,fields
     *
     * @apiResourceAdditional success=true message="Đổi trạng thái văn bản thành công!"
     */
    public function changeStatus(ChangeStatusDocumentRequest $request, Document $document)
    {
        $document = $this->documentService->changeStatus($document, $request->status);

        return $this->successResource(new DocumentResource($document), 'Đổi trạng thái văn bản thành công!');
    }

    /**
     * Xuất Excel danh sách văn bản
     *
     * Xuất ra các trường: id, so_ky_hieu, ten_van_ban, noi_dung, loai_van_ban, linh_vuc, co_quan_ban_hanh, cap_ban_hanh, nguoi_ky, ngay_ban_hanh, ngay_xuat_ban, ngay_hieu_luc, ngay_het_hieu_luc, status, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Tìm theo số ký hiệu hoặc tên văn bản.
     * @queryParam status string Trạng thái: active, inactive.
     * @queryParam document_type_id integer Lọc theo loại văn bản.
     * @queryParam document_field_id integer Lọc theo lĩnh vực.
     * @queryParam issuing_agency_id integer Lọc theo cơ quan ban hành.
     * @queryParam issuing_level_id integer Lọc theo cấp ban hành.
     * @queryParam signer_id integer Lọc theo người ký.
     */
    public function export(FilterRequest $request)
    {
        return $this->documentService->export($request->all());
    }

    /**
     * Import Excel văn bản
     *
     * Cột không bắt buộc: so_ky_hieu, ten_van_ban, noi_dung, status (mặc định "active"). Quan hệ loại/lĩnh vực/cơ quan/cấp/người ký cần cấu hình riêng.
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import văn bản thành công."}
     */
    public function import(ImportDocumentRequest $request)
    {
        $this->documentService->import($request->file('file'));

        return $this->success(null, 'Import văn bản thành công.');
    }
}
