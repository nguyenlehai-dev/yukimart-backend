<?php

namespace App\Modules\Post;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Requests\BulkDestroyPostRequest;
use App\Modules\Post\Requests\BulkUpdateStatusPostRequest;
use App\Modules\Post\Requests\ChangeStatusPostRequest;
use App\Modules\Post\Requests\ImportPostRequest;
use App\Modules\Post\Requests\StorePostRequest;
use App\Modules\Post\Requests\UpdatePostRequest;
use App\Modules\Post\Resources\PostCollection;
use App\Modules\Post\Resources\PostResource;
use App\Modules\Post\Services\PostService;

/**
 * @group Post - Post
 * @header X-Organization-Id ID tổ chức cần làm việc (bắt buộc với endpoint yêu cầu auth). Example: 1
 *
 * Quản lý bài viết: danh sách, chi tiết, tạo, cập nhật, xóa, thao tác hàng loạt
 */
class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    /**
     * Thống kê bài viết
     *
     * Tổng số, đang xuất bản (published), không xuất bản (draft, archived). Áp dụng cùng bộ lọc với index.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề). Example: hello
     * @queryParam status string Lọc theo trạng thái: draft, published, archived.
     * @queryParam category_id integer Lọc bài viết thuộc danh mục (ID). Example: 1
     * @queryParam sort_by string Sắp xếp theo: id, title, created_at, view_count. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @response 200 {"success": true, "data": {"total": 10, "active": 5, "inactive": 5}}
     */
    public function stats(FilterRequest $request)
    {
        return $this->success($this->postService->stats($request->all()));
    }

    /**
     * Danh sách bài viết
     *
     * Lấy danh sách có phân trang, lọc và sắp xếp.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề). Example: hello
     * @queryParam status string Lọc theo trạng thái: draft, published, archived.
     * @queryParam category_id integer Lọc bài viết thuộc danh mục (ID). Example: 1
     * @queryParam sort_by string Sắp xếp theo: id, title, created_at, view_count. Example: created_at
     * @queryParam sort_order string Thứ tự: asc, desc. Example: desc
     * @queryParam limit integer Số bản ghi mỗi trang (1-100). Example: 10
     *
     * @apiResourceCollection App\Modules\Post\Resources\PostCollection
     *
     * @apiResourceModel App\Modules\Post\Models\Post paginate=10
     *
     * @apiResourceAdditional success=true
     */
    public function index(FilterRequest $request)
    {
        $posts = $this->postService->index($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new PostCollection($posts));
    }

    /**
     * Chi tiết bài viết
     *
     * @urlParam post integer required ID bài viết. Example: 1
     *
     * @apiResource App\Modules\Post\Resources\PostResource
     *
     * @apiResourceModel App\Modules\Post\Models\Post with=categories
     *
     * @apiResourceAdditional success=true
     */
    public function show(Post $post)
    {
        $post = $this->postService->show($post);

        return $this->successResource(new PostResource($post));
    }

    /**
     * Tạo bài viết mới
     *
     * @bodyParam title string required Tiêu đề (duy nhất). Example: Bài viết mẫu
     * @bodyParam content string required Nội dung (tối thiểu 10 ký tự). Example: Nội dung bài viết...
     * @bodyParam status string required Trạng thái: draft, published, archived. Example: draft
     * @bodyParam category_ids array Mảng ID danh mục (tối đa 20). Example: [1, 2]
     * @bodyParam images[] file Ảnh đính kèm (jpeg/png/gif/webp, tối đa 10 ảnh, mỗi ảnh ≤ 5MB).
     *
     * @apiResource App\Modules\Post\Resources\PostResource status=201
     *
     * @apiResourceModel App\Modules\Post\Models\Post with=categories
     *
     * @apiResourceAdditional success=true message="Bài viết đã được tạo thành công!"
     */
    public function store(StorePostRequest $request)
    {
        $post = $this->postService->store($request->validated(), $request->file('images', []));

        return $this->successResource(new PostResource($post), 'Bài viết đã được tạo thành công!', 201);
    }

    /**
     * Cập nhật bài viết
     *
     * @urlParam post integer required ID bài viết. Example: 1
     *
     * @bodyParam title string Tiêu đề (duy nhất).
     * @bodyParam content string Nội dung (tối thiểu 10 ký tự).
     * @bodyParam status string Trạng thái: draft, published, archived.
     * @bodyParam category_ids array Mảng ID danh mục (ghi đè danh sách hiện tại).
     * @bodyParam images[] file Ảnh mới (append).
     * @bodyParam remove_attachment_ids array Mảng ID đính kèm cần xóa.
     *
     * @apiResource App\Modules\Post\Resources\PostResource
     *
     * @apiResourceModel App\Modules\Post\Models\Post with=categories
     *
     * @apiResourceAdditional success=true message="Bài viết đã được cập nhật!"
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $post = $this->postService->update($post, $request->validated(), $request->file('images', []));

        return $this->successResource(new PostResource($post), 'Bài viết đã được cập nhật!');
    }

    /**
     * Xóa bài viết
     *
     * @urlParam post integer required ID bài viết. Example: 1
     *
     * @response 200 {"success": true, "message": "Bài viết đã được xóa thành công!"}
     */
    public function destroy(Post $post)
    {
        $this->postService->destroy($post);

        return $this->success(null, 'Bài viết đã được xóa thành công!');
    }

    /**
     * Xóa hàng loạt bài viết
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     *
     * @response 200 {"success": true, "message": "Đã xóa thành công các bài viết được chọn!"}
     */
    public function bulkDestroy(BulkDestroyPostRequest $request)
    {
        $this->postService->bulkDestroy($request->ids);

        return $this->success(null, 'Đã xóa thành công các bài viết được chọn!');
    }

    /**
     * Cập nhật trạng thái hàng loạt bài viết
     *
     * @bodyParam ids array required Danh sách ID. Example: [1, 2, 3]
     * @bodyParam status string required Trạng thái: draft, published, archived. Example: published
     *
     * @response 200 {"success": true, "message": "Cập nhật trạng thái thành công các bài viết được chọn!"}
     */
    public function bulkUpdateStatus(BulkUpdateStatusPostRequest $request)
    {
        $this->postService->bulkUpdateStatus($request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái thành công các bài viết được chọn!');
    }

    /**
     * Xuất danh sách bài viết
     *
     * Áp dụng cùng bộ lọc với index. Xuất ra các trường: id, title, slug, content, status, view_count, categories, created_by, updated_by, created_at, updated_at.
     *
     * @queryParam search string Từ khóa tìm kiếm (tiêu đề).
     * @queryParam status string Lọc theo trạng thái: draft, published, archived.
     * @queryParam category_id integer Lọc bài viết thuộc danh mục (ID).
     * @queryParam sort_by string Sắp xếp theo: id, title, created_at, view_count.
     * @queryParam sort_order string Thứ tự: asc, desc.
     */
    public function export(FilterRequest $request)
    {
        return $this->postService->export($request->all());
    }

    /**
     * Nhập danh sách bài viết
     *
     * Cột bắt buộc: title, content. Cột không bắt buộc: status (mặc định "published"), categories (tên nối phẩy).
     *
     * @bodyParam file file required File Excel (xlsx, xls, csv). Cột theo chuẩn export.
     *
     * @response 200 {"success": true, "message": "Import bài viết thành công."}
     */
    public function import(ImportPostRequest $request)
    {
        $this->postService->import($request->file('file'));

        return $this->success(null, 'Import bài viết thành công.');
    }

    /**
     * Thay đổi trạng thái bài viết
     *
     * @urlParam post integer required ID bài viết. Example: 1
     *
     * @bodyParam status string required Trạng thái mới: draft, published, archived. Example: published
     *
     * @apiResource App\Modules\Post\Resources\PostResource
     *
     * @apiResourceModel App\Modules\Post\Models\Post with=categories
     *
     * @apiResourceAdditional success=true message="Cập nhật trạng thái thành công!"
     */
    public function changeStatus(ChangeStatusPostRequest $request, Post $post)
    {
        $post = $this->postService->changeStatus($post, $request->status);

        return $this->successResource(new PostResource($post), 'Cập nhật trạng thái thành công!');
    }

    /**
     * Tăng lượt xem bài viết (gọi khi người dùng xem chi tiết).
     *
     * @urlParam post integer required ID bài viết. Example: 1
     *
     * @response 200 {"success": true, "data": {"view_count": 1}, "message": "Đã cập nhật lượt xem."}
     */
    public function incrementView(Post $post)
    {
        $viewCount = $this->postService->incrementView($post);

        return $this->success(['view_count' => $viewCount], 'Đã cập nhật lượt xem.');
    }
}
