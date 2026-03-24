# Phân tích chi tiết chức năng PostCategory

**Ngày tạo:** 2026-02-16  
**Mục đích:** Mô tả đầy đủ module Danh mục tin tức (Post Category): kiến trúc, API, model, validation, cấu trúc cây (Nested Set).

---

## 1. Tổng quan

PostCategory quản lý **danh mục tin tức phân cấp** (cấu trúc cây cha–con), dùng gói **kalnoy/nestedset** (Nested Set Model). Cấu trúc và danh sách action đồng bộ với module User và Post (CRUD, bulk, export/import, changeStatus).

- **Prefix API:** `/api/post-categories`
- **Model:** `App\Modules\Post\Models\PostCategory`
- **Controller:** `App\Modules\Post\PostCategoryController`

---

## 2. Cơ sở dữ liệu

### 2.1 Bảng `post_categories`

| Cột          | Kiểu         | Mô tả |
|-------------|--------------|--------|
| id          | bigint PK    | |
| name        | string       | Tên danh mục |
| slug        | string       | Unique, dùng cho URL |
| description | text         | Nullable |
| status      | string       | active, inactive (default: active) |
| sort_order  | unsigned int | Thứ tự hiển thị (default: 0) |
| parent_id   | bigint       | Nullable, self-reference (Nested Set) |
| _lft        | unsigned int | Nested Set – cận trái |
| _rgt        | unsigned int | Nested Set – cận phải |
| created_by  | bigint FK    | Nullable → users.id |
| updated_by  | bigint FK    | Nullable → users.id |
| created_at  | timestamp    | |
| updated_at  | timestamp    | |

- **Nested Set:** `_lft`, `_rgt`, `parent_id` do package quản lý; không gán tay. Xóa một node sẽ xóa toàn bộ con.

### 2.2 Quan hệ

- **PostCategory** → `creator`, `editor` (belongsTo User), `parent`, `children` (Nested Set), `posts` (hasMany Post).
- **Post** → `category` (belongsTo PostCategory).

---

## 3. Model PostCategory

### 3.1 Trait & factory

- **HasFactory:** `newFactory()` trỏ tới `Database\Factories\PostCategoryFactory::new()`.
- **NodeTrait (kalnoy/nestedset):** Cung cấp `parent`, `children`, `ancestors`, `descendants`, `appendToNode()`, `saveAsRoot()`, `whereIsRoot()`, `withDepth()`, `defaultOrder()`, `toTree()`, v.v.

### 3.2 Fillable

`name`, `slug`, `description`, `status`, `sort_order`, `created_by`, `updated_by`.

### 3.3 Logic trong `booted()`

- **creating:** Gán `created_by`, `updated_by` = `auth()->id()`. Nếu `slug` rỗng thì sinh từ `name` bằng `uniqueSlug()`.
- **updating:** Gán `updated_by` = `auth()->id()`. Nếu đổi `name` mà không đổi `slug` thì cập nhật slug từ name (vẫn qua `uniqueSlug` để tránh trùng).

### 3.4 Slug duy nhất

- **uniqueSlug(string $base, ?int $excludeId):** Trả về slug chưa tồn tại (nếu trùng thì thêm `-1`, `-2`, …). Dùng khi tạo/cập nhật trong app.
- **uniqueSlugForImport(string $base):** Bọc `uniqueSlug($base, null)` cho Import.

### 3.5 Scope

- **scopeFilter($query, array $filters):**  
  - `search`: LIKE name.  
  - `status`: bằng status.  
  - `sort_by` / `sort_order`: chỉ cho phép cột `id`, `name`, `sort_order`, `created_at`; mặc định sort theo `sort_order` asc.

---

## 4. API Endpoints

| Method | Path | Action | Mô tả |
|--------|------|--------|--------|
| GET | /api/post-categories/export | export | Xuất Excel (cây: cha trước con) |
| POST | /api/post-categories/import | import | Nhập Excel (file xlsx/xls/csv) |
| POST | /api/post-categories/bulk-delete | bulkDestroy | Xóa hàng loạt theo `ids` |
| PATCH | /api/post-categories/bulk-status | bulkUpdateStatus | Cập nhật status hàng loạt |
| GET | /api/post-categories/tree | tree | Cây toàn bộ (parent–children), có thể lọc status |
| GET | /api/post-categories | index | Danh sách phẳng, phân trang, lọc, sắp xếp |
| GET | /api/post-categories/{id} | show | Chi tiết một danh mục |
| POST | /api/post-categories | store | Tạo danh mục (gốc hoặc con) |
| PUT/PATCH | /api/post-categories/{id} | update | Cập nhật (có thể đổi parent, hoặc chuyển thành gốc với parent_id=0) |
| DELETE | /api/post-categories/{id} | destroy | Xóa (xóa luôn toàn bộ con) |
| PATCH | /api/post-categories/{id}/status | changeStatus | Đổi trạng thái một danh mục |

**Lưu ý route:** Các route cụ thể (`/export`, `/import`, `/bulk-delete`, `/bulk-status`, `/tree`) phải khai báo trước route `/{category}` để tránh bị coi là ID.

---

## 5. Chi tiết từng action

### 5.1 index (danh sách phẳng)

- **Request:** `App\Modules\Core\Requests\FilterRequest` — query: `search`, `status`, `sort_by`, `sort_order`, `limit` (1–100).
- **Logic:** `PostCategory::with(['creator','editor'])->withDepth()->filter($request->all())->paginate($request->limit ?? 10)`.
- **Response:** `PostCategoryCollection` (paginated), mỗi item có `depth`, `created_by`/`updated_by` (tên user).

### 5.2 tree (cây toàn bộ)

- **Request:** Query `status` (optional).
- **Logic:** Lấy toàn bộ node (có lọc status) → `defaultOrder()->withDepth()->get()` → `toTree()`.
- **Response:** Collection dạng cây: mỗi node có `children` (đệ quy). Không phân trang.

### 5.3 show (chi tiết)

- **Route model binding:** `{category}` → `PostCategory`.
- **Logic:** Load lại với `creator`, `editor`, `parent`, `children` (children defaultOrder), và `withDepth()`.
- **Response:** `PostCategoryResource` (có parent, children khi load).

### 5.4 store (tạo mới)

- **Request:** `StorePostCategoryRequest`: `name` (required), `slug` (nullable, unique), `description`, `status` (required: active|inactive), `sort_order`, `parent_id` (nullable, exists:post_categories,id).
- **Logic:**  
  - Có `parent_id`: `(new PostCategory($data))->appendToNode($parent)->save()`.  
  - Không có `parent_id`: `$category->saveAsRoot()`.
- **Response:** 201 + `PostCategoryResource` + message.

### 5.5 update (cập nhật)

- **Request:** `UpdatePostCategoryRequest`: các field giống store (sometimes/nullable). `parent_id` có thể gửi để đổi cha hoặc gửi `0` để chuyển thành gốc (khi đó validation `exists` có thể cần ngoại lệ cho 0).
- **Logic:**  
  - `parent_id === 0`: `saveAsRoot()`.  
  - `parent_id` khác và khác parent hiện tại: `appendToNode($parent)->save()`.  
  - Còn lại: `$category->save()`.
- **Response:** `PostCategoryResource` + message.

### 5.6 destroy (xóa một danh mục)

- **Logic:** `$category->delete()`. Nested Set sẽ xóa toàn bộ node con.
- **Response:** JSON message.

### 5.7 bulkDestroy (xóa hàng loạt)

- **Request:** `BulkDestroyPostCategoryRequest`: `ids` (array, required, exists:post_categories,id).
- **Logic:** `PostCategory::whereIn('id', $request->ids)->get()->each->delete()` (mỗi node xóa theo Nested Set).
- **Response:** JSON message.

### 5.8 bulkUpdateStatus (cập nhật trạng thái hàng loạt)

- **Request:** `BulkUpdateStatusPostCategoryRequest`: `ids` (array), `status` (required: active|inactive).
- **Logic:** `PostCategory::whereIn('id', $request->ids)->update(['status' => $request->status])`.
- **Response:** JSON message.

### 5.9 export (xuất Excel)

- **Logic:** `PostCategoriesExport` — lấy danh mục `defaultOrder()->withDepth()->get()`, map ra các cột: id, name, slug, description, status, sort_order, parent_slug, depth.
- **Response:** File `post-categories.xlsx` (thứ tự cây: cha trước con).

### 5.10 import (nhập Excel)

- **Request:** `ImportPostCategoryRequest`: `file` (required, xlsx|xls|csv).
- **Logic:** `PostCategoriesImport` — đọc từng dòng; cột: name, slug (hoặc sinh từ name), description, status, sort_order, parent_slug. Nếu có `parent_slug` thì tìm parent theo slug và `appendToNode($parent)`; slug được làm unique qua `uniqueSlugForImport()`.
- **Response:** JSON message.

### 5.11 changeStatus (đổi trạng thái một danh mục)

- **Request:** `ChangeStatusPostCategoryRequest`: `status` (required: active|inactive).
- **Logic:** `$category->update(['status' => $request->status])`.
- **Response:** JSON message + `data` (PostCategoryResource).

---

## 6. Request validation (tóm tắt)

| Request | Rule chính |
|---------|------------|
| StorePostCategoryRequest | name required; slug nullable, unique; status required in:active,inactive; parent_id nullable, exists |
| UpdatePostCategoryRequest | name/slug/status sometimes; slug unique trừ chính nó; parent_id nullable, exists |
| BulkDestroyPostCategoryRequest | ids required, array, min:1, ids.* exists:post_categories,id |
| BulkUpdateStatusPostCategoryRequest | ids required, array; status required in:active,inactive |
| ChangeStatusPostCategoryRequest | status required in:active,inactive |
| ImportPostCategoryRequest | file required, mimes:xlsx,xls,csv |

**Lưu ý:** Khi cần “chuyển thành gốc” bằng `parent_id: 0`, rule `parent_id exists:post_categories,id` sẽ không chấp nhận 0. Có thể bổ sung rule dạng “nullable hoặc 0 hoặc exists” nếu muốn validate đầy đủ ở tầng FormRequest.

---

## 7. Resource (response)

**PostCategoryResource** trả về:

- id, name, slug, description, status, sort_order, parent_id, depth.
- created_by, updated_by (tên user từ relation).
- created_at, updated_at.
- parent (khi load) — PostCategoryResource.
- children (khi load) — PostCategoryResource collection.

**PostCategoryCollection:** Paginated collection của `PostCategoryResource`.

---

## 8. Cấu trúc file trong module

```
app/Modules/Post/
├── Models/PostCategory.php
├── PostCategoryController.php
├── Routes/post_category.php
├── Requests/
│   ├── StorePostCategoryRequest.php
│   ├── UpdatePostCategoryRequest.php
│   ├── BulkDestroyPostCategoryRequest.php
│   ├── BulkUpdateStatusPostCategoryRequest.php
│   ├── ChangeStatusPostCategoryRequest.php
│   └── ImportPostCategoryRequest.php
├── Resources/
│   ├── PostCategoryResource.php
│   └── PostCategoryCollection.php
├── Exports/PostCategoriesExport.php
└── Imports/PostCategoriesImport.php
```

Routes được load trong `routes/api.php` với prefix `post-categories`.

---

## 9. Điểm cần lưu ý khi mở rộng

- **Chuyển nhánh (đổi parent):** Có thể cần kiểm tra không cho chuyển node vào chính con của nó (tránh vòng).
- **parent_id = 0 khi update:** Nên cho phép 0 trong validation (ví dụ rule tùy biến) để “chuyển thành gốc” không lỗi validate.
- **Import thứ tự:** File Excel nên sắp dòng theo thứ tự cây (cha trước con) vì con cần parent đã tồn tại (theo slug).
- **Xóa danh mục:** Bài viết (Post) có `category_id` trỏ tới danh mục; migration có thể dùng `nullOnDelete()` để khi xóa category thì post.category_id = null, hoặc cấm xóa nếu còn post.

Tài liệu này mô tả đúng với code hiện tại; khi đổi logic hoặc validation nên cập nhật lại doc tương ứng.
