# API Danh mục tin tức (Post Category)

Quản lý danh mục tin tức phân cấp theo cấu trúc cây `parent_id`: thống kê, danh sách, cây, CRUD, xóa/bulk status, đổi trạng thái, xuất/nhập Excel.

**Base path:** `/api/post-categories`

---

## Thống kê

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/stats` |
| **Query** | `search` (name), `status` (active \| inactive), `from_date` (Y-m-d), `to_date` (Y-m-d), `sort_by`, `sort_order`, `limit` (1-100). Cùng bộ lọc với index. |
| **Response** | `{ "total": 15, "active": 12, "inactive": 3 }`. |

---

## Danh sách danh mục (phẳng, phân trang)

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories` |
| **Query** | `search`, `status`, `from_date`, `to_date`, `sort_by` (id \| name \| sort_order \| parent_id \| created_at), `sort_order` (asc \| desc), `limit` (1-100). Thứ tự theo cây (treeOrder). |
| **Response** | Paginated collection (PostCategoryResource), mỗi item có `creator`, `editor`, `parent`. |

---

## Danh sách danh mục công khai

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/public` |
| **Auth** | Không cần |
| **Query** | `search` (name). Chỉ trả dữ liệu `active`, sắp xếp theo thứ tự cây. |
| **Response** | Collection không phân trang (PostCategoryResource), phù hợp cho dropdown/chọn danh mục. |

---

## Danh sách danh mục công khai (dropdown tối ưu)

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/public-options` |
| **Auth** | Không cần |
| **Query** | `search` (name). Chỉ trả dữ liệu `active`, sắp xếp theo thứ tự cây. |
| **Response** | Collection không phân trang với 3 trường: `id`, `name`, `description`. |

---

## Cây danh mục

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/tree` |
| **Query** | `status` (active \| inactive). |
| **Response** | Mảng cây (không phân trang), mỗi node có `children` đệ quy — PostCategoryTreeResource. |

---

## Chi tiết danh mục

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/{id}` |
| **UrlParam** | `id` — ID danh mục. |
| **Response** | Object danh mục (PostCategoryResource), kèm `parent`, `children`. |

---

## Tạo danh mục

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/post-categories` |
| **Body** | `name` (required), `slug` (optional, tự sinh từ name), `description` (optional), `status` (required: active \| inactive), `parent_id` (optional, null = gốc), `sort_order` (optional). |
| **Response** | 201, object danh mục + `"message": "Danh mục đã được tạo thành công!"`. |

---

## Cập nhật danh mục

| | |
|---|---|
| **Method** | PUT / PATCH |
| **Path** | `/api/post-categories/{id}` |
| **Body** | `name`, `slug`, `description`, `status`, `parent_id` (null hoặc 0 = gốc), `sort_order`. Không được chọn danh mục con làm danh mục cha. |
| **Response** | Object danh mục + `"message": "Danh mục đã được cập nhật!"`. |

---

## Xóa danh mục

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/post-categories/{id}` |
| **Response** | `{ "message": "Danh mục đã được xóa!" }`. Xóa cả danh mục con (cascade). |

---

## Xóa hàng loạt

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/post-categories/bulk-delete` |
| **Body** | `ids` (array) — danh sách ID danh mục. |
| **Response** | `{ "message": "Đã xóa thành công các danh mục được chọn!" }`. |

---

## Cập nhật trạng thái hàng loạt

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/post-categories/bulk-status` |
| **Body** | `ids` (array), `status` (required: active \| inactive). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công các danh mục được chọn!" }`. |

---

## Đổi trạng thái danh mục

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/post-categories/{id}/status` |
| **Body** | `status` (required: active \| inactive). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công!", "data": PostCategoryResource }`. |

---

## Xuất Excel

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/post-categories/export` |
| **Query** | Cùng bộ lọc với index: `search`, `status`, `from_date`, `to_date`, `sort_by`, `sort_order`. |
| **Response** | File `post-categories.xlsx`. |

---

## Nhập Excel

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/post-categories/import` |
| **Body** | `file` (required) — xlsx, xls, csv. Cột: name, slug, description, status, sort_order, parent_slug. |
| **Response** | `{ "message": "Post categories imported successfully." }`. |

---

## Response mẫu (PostCategoryResource)

```json
{
  "id": 1,
  "name": "Tin tức",
  "slug": "tin-tuc",
  "description": "Mô tả",
  "status": "active",
  "parent_id": null,
  "sort_order": 0,
  "depth": 0,
  "created_by": "Admin",
  "updated_by": "Admin",
  "created_at": "14:30:00 17/02/2026",
  "updated_at": "14:30:00 17/02/2026"
}
```
