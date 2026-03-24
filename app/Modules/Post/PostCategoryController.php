<?php

namespace App\Modules\Post;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Core\Resources\PublicOptionResource;
use App\Modules\Post\Models\PostCategory;
use App\Modules\Post\Requests\BulkDestroyPostCategoryRequest;
use App\Modules\Post\Requests\BulkUpdateStatusPostCategoryRequest;
use App\Modules\Post\Requests\ChangeStatusPostCategoryRequest;
use App\Modules\Post\Requests\ImportPostCategoryRequest;
use App\Modules\Post\Requests\StorePostCategoryRequest;
use App\Modules\Post\Requests\UpdatePostCategoryRequest;
use App\Modules\Post\Resources\PostCategoryCollection;
use App\Modules\Post\Resources\PostCategoryResource;
use App\Modules\Post\Resources\PostCategoryTreeResource;
use App\Modules\Post\Services\PostCategoryService;
use Illuminate\Http\Request;

/**
 * @group Post - Category
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý danh mục tin tức phân cấp (cấu trúc cây parent_id): danh sách, cây, chi tiết, tạo, cập nhật, xóa, thao tác hàng loạt, xuất/nhập, đổi trạng thái.
 */
class PostCategoryController extends Controller
{
    public function __construct(private PostCategoryService $postCategoryService) {}

    /**
     * Danh sách danh mục công khai
     *
     * Trả về danh sách danh mục đang hoạt động (active), thứ tự theo cây, dùng cho các chức năng công khai.
     *
     * @unauthenticated
     *
     * @queryParam search string Từ khóa tìm kiếm (name). Example: tin-tuc
     *
     * @apiResourceCollection App\Modules\Post\Resources\PostCategoryCollection
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory
     *
     * @apiResourceAdditional success=true
     */
    public function public(FilterRequest $request)
    {
        $categories = $this->postCategoryService->publicList($request->all());

        return $this->successCollection(new PostCategoryCollection($categories));
    }

    /**
     * Danh sách danh mục công khai cho dropdown
     *
     * Trả về dữ liệu tối giản chỉ gồm id, name, description để tối ưu payload cho dropdown.
     *
     * @unauthenticated
     *
     * @queryParam search string Từ khóa tìm kiếm (name). Example: tin-tuc
     *
     * @apiResourceCollection App\Modules\Core\Resources\PublicOptionResource
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory
     *
     * @apiResourceAdditional success=true
     */
    public function publicOptions(FilterRequest $request)
    {
        $categories = $this->postCategoryService->publicOptions($request->all());

        return $this->successCollection(PublicOptionResource::collection($categories));
    }

    /**
     * Thống kê danh mục tin tức
     *
     * Tổng số, đang kích hoạt (active), không kích hoạt (inactive). Áp dụng cùng bộ lọc với index.
     *
     * @queryParam search string Từ khóa tìm kiếm (name). Example: tin-tuc
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     * @queryParam from_date date Lọc từ ngày tạo (created_at) (Y-m-d). Example: 2026-02-01
     * @queryParam to_date date Lọc đến ngày tạo (created_at) (Y-m-d). Example: 2026-02-17
     * @queryParam sort_by string Sắp xếp theo: id, name, sort_order, parent_id, created_at. Example: sort_order
     * @queryParam sort_order string Thứ tự: asc, desc. Example: asc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @response 200 {"success": true, "data": {"total": 10, "active": 5, "inactive": 5}}
     */
    public function stats(FilterRequest $request)
    {
        return $this->success($this->postCategoryService->stats($request->all()));
    }

    /**
     * Danh sách danh mục (dạng phẳng, phân trang, thứ tự cây)
     *
     * @queryParam search string Từ khóa tìm kiếm (name). Example: tin-tuc
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     * @queryParam from_date date Lọc từ ngày tạo (created_at) (Y-m-d). Example: 2026-02-01
     * @queryParam to_date date Lọc đến ngày tạo (created_at) (Y-m-d). Example: 2026-02-17
     * @queryParam sort_by string Sắp xếp theo: id, name, sort_order, parent_id, created_at. Example: sort_order
     * @queryParam sort_order string Thứ tự: asc, desc. Example: asc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @apiResourceCollection App\Modules\Post\Resources\PostCategoryCollection
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory paginate=10
     *
     * @apiResourceAdditional success=true
     */
    public function index(FilterRequest $request)
    {
        $categories = $this->postCategoryService->index($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new PostCategoryCollection($categories));
    }

    /**
     * Cây danh mục (toàn bộ cây, không phân trang). Cấu trúc parent_id, children đệ quy.
     *
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     *
     * @response 200 {"success": true, "data": [{"id": 1, "name": "Tin tức", "slug": "tin-tuc", "status": "active", "sort_order": 0, "parent_id": null, "depth": 0, "children": []}]}
     */
    public function tree(Request $request)
    {
        $tree = $this->postCategoryService->tree($request->status);

        return $this->successCollection(PostCategoryTreeResource::collection($tree));
    }

    /**
     * Chi tiết danh mục
     *
     * @urlParam category integer required ID danh mục. Example: 1
     *
     * @apiResource App\Modules\Post\Resources\PostCategoryResource
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory with=parent,children
     *
     * @apiResourceAdditional success=true
     */
    public function show(PostCategory $category)
    {
        $category = $this->postCategoryService->show($category);

        return $this->successResource(new PostCategoryResource($category));
    }

    /**
     * Tạo danh mục mới
     *
     * @bodyParam name string required Tên danh mục. Example: Tin tức
     * @bodyParam slug string Slug (tự sinh từ name nếu không gửi). Example: tin-tuc
     * @bodyParam description string Mô tả.
     * @bodyParam status string required Trạng thái: active, inactive. Example: active
     * @bodyParam parent_id integer ID danh mục cha (null = gốc). Example: null
     * @bodyParam sort_order integer Thứ tự. Example: 0
     *
     * @apiResource App\Modules\Post\Resources\PostCategoryResource status=201
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory
     *
     * @apiResourceAdditional success=true message="Danh mục đã được tạo thành công!"
     */
    public function store(StorePostCategoryRequest $request)
    {
        $category = $this->postCategoryService->store($request->validated());

        return $this->successResource(new PostCategoryResource($category), 'Danh mục đã được tạo thành công!', 201);
    }

    /**
     * Cập nhật danh mục
     *
     * @urlParam category integer required ID danh mục. Example: 1
     *
     * @bodyParam name string Tên danh mục.
     * @bodyParam slug string Slug.
     * @bodyParam description string Mô tả.
     * @bodyParam status string Trạng thái: active, inactive.
     * @bodyParam parent_id integer ID danh mục cha (null hoặc 0 = gốc).
     * @bodyParam sort_order integer Thứ tự.
     *
     * @apiResource App\Modules\Post\Resources\PostCategoryResource
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory with=parent,children
     *
     * @apiResourceAdditional success=true message="Danh mục đã được cập nhật!"
     */
    public function update(UpdatePostCategoryRequest $request, PostCategory $category)
    {
        $result = $this->postCategoryService->update($category, $request->validated());
        if (! $result['ok']) {
            return $this->error($result['message'], $result['code'], null, $result['error_code']);
        }

        return $this->successResource(new PostCategoryResource($result['category']), 'Danh mục đã được cập nhật!');
    }

    /**
     * Xóa danh mục
     *
     * @urlParam category integer required ID danh mục. Example: 1
     *
     * @response 200 {"success": true, "message": "Danh mục đã được xóa!"}
     */
    public function destroy(PostCategory $category)
    {
        $this->postCategoryService->destroy($category);

        return $this->success(null, 'Danh mục đã được xóa!');
    }

    /**
     * Xóa hàng loạt danh mục
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     *
     * @response 200 {"success": true, "message": "Đã xóa thành công các danh mục được chọn!"}
     */
    public function bulkDestroy(BulkDestroyPostCategoryRequest $request)
    {
        $this->postCategoryService->bulkDestroy($request->ids);

        return $this->success(null, 'Đã xóa thành công các danh mục được chọn!');
    }

    /**
     * Cập nhật trạng thái danh mục hàng loạt
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     * @bodyParam status string required Trạng thái: active, inactive. Example: active
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái thành công các danh mục được chọn!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusPostCategoryRequest $request)
    {
        $this->postCategoryService->bulkUpdateStatus($request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái thành công các danh mục được chọn!');
    }

    /**
     * Xuất danh sách danh mục
     *
     * Áp dụng cùng bộ lọc với index. Xuất ra các trường: id, name, slug, description, status, sort_order, parent_id, parent_slug, depth, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Từ khóa tìm kiếm (name).
     * @queryParam status string Lọc theo trạng thái: active, inactive.
     * @queryParam from_date date Lọc từ ngày tạo (Y-m-d).
     * @queryParam to_date date Lọc đến ngày tạo (Y-m-d).
     * @queryParam sort_by string Sắp xếp theo: id, name, sort_order, parent_id, created_at.
     * @queryParam sort_order string Thứ tự: asc, desc.
     */
    public function export(FilterRequest $request)
    {
        return $this->postCategoryService->export($request->all());
    }

    /**
     * Nhập danh sách danh mục
     *
     * Cột bắt buộc: name. Cột không bắt buộc: slug (tự sinh từ name), description, status (mặc định "active"), sort_order (mặc định 0), parent_slug (slug của danh mục cha).
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import danh mục bài viết thành công."}
     */
    public function import(ImportPostCategoryRequest $request)
    {
        $this->postCategoryService->import($request->file('file'));

        return $this->success(null, 'Import danh mục bài viết thành công.');
    }

    /**
     * Thay đổi trạng thái danh mục
     *
     * @urlParam category integer required ID danh mục. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: active, inactive. Example: active
     *
     * @apiResource App\Modules\Post\Resources\PostCategoryResource
     *
     * @apiResourceModel App\Modules\Post\Models\PostCategory with=parent,children
     *
     * @apiResourceAdditional success=true message="Cập nhật trạng thái thành công!"
     */
    public function changeStatus(ChangeStatusPostCategoryRequest $request, PostCategory $category)
    {
        $category = $this->postCategoryService->changeStatus($category, $request->status);

        return $this->successResource(new PostCategoryResource($category), 'Cập nhật trạng thái thành công!');
    }
}
