# API Bài viết (Post)

Quản lý bài viết tin tức: thống kê, danh sách, chi tiết, CRUD, xóa/bulk status, đổi trạng thái, tăng lượt xem, xuất/nhập Excel. Một bài viết thuộc nhiều danh mục; hỗ trợ đính kèm ảnh và `view_count`.

**Base path:** `/api/posts`

---

## Thống kê

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/posts/stats` |
| **Query** | `search` (tiêu đề), `status` (draft \| published \| archived), `category_id` (ID danh mục), `sort_by`, `sort_order`, `limit` (1-100). |
| **Response** | `{ "total": 50, "active": 30, "inactive": 20 }` — total (sau lọc), active = published, inactive = draft + archived. |

---

## Danh sách bài viết

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/posts` |
| **Query** | `search`, `status`, `category_id`, `sort_by` (id \| title \| created_at \| view_count), `sort_order` (asc \| desc), `limit` (1-100). |
| **Response** | Paginated collection; mỗi item có `categories`, `view_count`. |

---

## Chi tiết bài viết

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/posts/{id}` |
| **UrlParam** | `id` — ID bài viết. |
| **Response** | Object bài viết (PostResource), kèm `categories`, `attachments`, `view_count`. |

---

## Tạo bài viết

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/posts` |
| **Body** | `title` (required), `content` (required, min 10 ký tự), `status` (required: draft \| published \| archived), `category_ids` (optional, mảng ID, tối đa 20), `images[]` (optional, ảnh jpeg/png/gif/webp, tối đa 10 ảnh, mỗi ảnh ≤ 5MB). Form-data hoặc JSON. |
| **Response** | 201, object bài viết (kèm categories, attachments) + `"message": "Bài viết đã được tạo thành công!"`. |

---

## Cập nhật bài viết

| | |
|---|---|
| **Method** | PUT / PATCH |
| **Path** | `/api/posts/{id}` |
| **Body** | Giống tạo (các field tùy chọn). Thêm: `category_ids` (ghi đè danh sách danh mục), `images[]` (ảnh mới append), `remove_attachment_ids` (mảng ID media đính kèm cần xóa). |
| **Response** | Object bài viết đã cập nhật (kèm categories, attachments). |

---

## Xóa bài viết

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/posts/{id}` |
| **Response** | `{ "message": "Bài viết đã được xóa thành công!" }`. |

---

## Xóa hàng loạt

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/posts/bulk-delete` |
| **Body** | `ids` (array) — danh sách ID bài viết. |
| **Response** | `{ "message": "Đã xóa thành công các bài viết được chọn!" }`. |

---

## Cập nhật trạng thái hàng loạt

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/posts/bulk-status` |
| **Body** | `ids` (array), `status` (required: draft \| published \| archived). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công các bài viết được chọn!" }`. |

---

## Đổi trạng thái bài viết

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/posts/{id}/status` |
| **Body** | `status` (required: draft \| published \| archived). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công!", "data": PostResource }`. |

---

## Tăng lượt xem

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/posts/{id}/view` |
| **Response** | `{ "message": "Đã cập nhật lượt xem.", "view_count": 123 }`. |

---

## Xuất Excel

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/posts/export` |
| **Query** | Cùng bộ lọc với index: `search`, `status`, `category_id`, `sort_by`, `sort_order`. |
| **Response** | File `posts.xlsx`. |

---

## Nhập Excel

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/posts/import` |
| **Body** | `file` (required) — xlsx, xls, csv. Cột theo chuẩn export. |
| **Response** | `{ "message": "Posts imported successfully." }`. |

---

## Response mẫu (PostResource)

```json
{
  "id": 1,
  "title": "Bài viết mẫu",
  "content": "Nội dung...",
  "status": "published",
  "view_count": 0,
  "categories": [{ "id": 1, "name": "Tin tức", "slug": "tin-tuc" }],
  "attachments": [],
  "created_at": "14:30:00 17/02/2026",
  "updated_at": "14:30:00 17/02/2026"
}
```
