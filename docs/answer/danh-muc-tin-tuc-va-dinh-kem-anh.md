# Phân tích và bổ sung: Đính kèm hình ảnh cho tin tức + Danh mục tin tức phân cấp (Nested Set)

**Ngày tạo:** 2025-02-16  
**Mục đích:** Ghi lại phân tích và cách triển khai hai chức năng: đính kèm ảnh cho bài viết, quản lý danh mục tin tức phân cấp theo cấu trúc cây (kalnoy/nestedset).

---

## 1. Tổng quan

### 1.1 Đính kèm hình ảnh cho tin tức

- **Bảng:** `post_attachments` (post_id, path, disk, original_name, mime_type, size, sort_order).
- **Lưu file:** `storage/app/public/post-attachments/{post_id}/` (disk `public`), dùng `php artisan storage:link` để phục vụ URL.
- **API:** Trong `POST /api/posts` và `PUT/PATCH /api/posts/{post}`:
  - Gửi thêm `images[]` (mảng file ảnh, tối đa 10 ảnh, mỗi ảnh tối đa 5MB, jpeg/png/gif/webp).
  - Cập nhật: gửi thêm `remove_attachment_ids[]` để xóa đính kèm theo ID.
- **Response:** Bài viết trả về có quan hệ `attachments` (id, url, original_name, mime_type, size, sort_order).

### 1.2 Danh mục tin tức phân cấp (cây)

- **Gói:** `kalnoy/nestedset` (Nested Set Model).
- **Bảng:** `post_categories` (id, name, slug, description, status, sort_order, parent_id, _lft, _rgt, timestamps).
- **Model:** `App\Modules\Post\Models\PostCategory` dùng `NodeTrait` (parent, children, ancestors, descendants, defaultOrder(), withDepth(), toTree()...).
- **Bài viết:** Bảng `posts` thêm `category_id` (nullable FK -> post_categories.id). Post thuộc một danh mục.

---

## 2. API Danh mục tin tức (Post Category)

| Method | Path | Mô tả |
|--------|------|-------|
| GET | /api/post-categories | Danh sách phẳng, phân trang, lọc search/status, sắp xếp |
| GET | /api/post-categories/tree | Cây toàn bộ danh mục (parent-children), không phân trang |
| GET | /api/post-categories/{id} | Chi tiết một danh mục (kèm parent, children) |
| POST | /api/post-categories | Tạo danh mục (parent_id = null là gốc, khác null là con) |
| PUT/PATCH | /api/post-categories/{id} | Cập nhật; gửi parent_id để chuyển nhánh (0 = chuyển thành gốc) |
| DELETE | /api/post-categories/{id} | Xóa (xóa luôn toàn bộ con theo nested set) |

---

## 3. API Bài viết (Post) – bổ sung

- **Lọc:** `GET /api/posts?category_id=1` — lọc theo danh mục.
- **Tạo/Cập nhật:** Body có thêm `category_id` (nullable), `images[]` (file), `remove_attachment_ids[]` (khi cập nhật).
- **Response:** Có thêm `category_id`, `category` (object), `attachments` (mảng url + meta).

---

## 4. Cấu trúc file đã thêm/sửa

- **Migration:** `2026_02_16_100000_create_post_categories_table.php`, `2026_02_16_100001_create_post_attachments_table.php`, `2026_02_16_100002_add_category_id_to_posts_table.php`.
- **Model:** `PostCategory` (NodeTrait), `PostAttachment`, cập nhật `Post` (fillable, category, attachments, scopeFilter category_id).
- **Controller:** `PostCategoryController` (index, tree, show, store, update, destroy).
- **Routes:** `app/Modules/Post/Routes/post_category.php`, đăng ký prefix `post-categories` trong `routes/api.php`.
- **Request:** `StorePostCategoryRequest`, `UpdatePostCategoryRequest`; bổ sung `StorePostRequest`, `UpdatePostRequest` (category_id, images, remove_attachment_ids).
- **Resource:** `PostCategoryResource`; cập nhật `PostResource` (category, attachments).
- **Tài liệu DB:** `docs/DATABASE_DESIGN.md`.

---

## 5. Chạy migration và storage link

```bash
composer install   # cài kalnoy/nestedset nếu chưa
php artisan migrate
php artisan storage:link   # để URL ảnh đính kèm hoạt động
```

Sau khi triển khai, có thể tạo danh mục gốc rồi tạo danh mục con (parent_id), và đăng bài viết kèm ảnh + category_id.
