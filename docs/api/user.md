# API Người dùng (User) – Core

Quản lý tài khoản người dùng: thống kê, danh sách, chi tiết, CRUD, xóa/bulk status, đổi trạng thái, xuất/nhập Excel.

**Base path:** `/api/users`

---

## Thống kê

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/users/stats` |
| **Query** | `search` (name, email), `status` (active \| inactive \| banned), `from_date` (Y-m-d), `to_date` (Y-m-d), `sort_by`, `sort_order`, `limit` (1-100). Cùng bộ lọc với index. |
| **Response** | `{ "total": 100, "active": 80, "inactive": 20 }` — total (sau lọc), active, inactive (gồm banned). |

---

## Danh sách người dùng

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/users` |
| **Query** | `search` (name, email), `status` (active \| inactive \| banned), `from_date`, `to_date`, `sort_by` (id \| name \| created_at), `sort_order` (asc \| desc), `limit` (1-100). |
| **Response** | Paginated collection (UserResource). |

---

## Chi tiết người dùng

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/users/{id}` |
| **UrlParam** | `id` — ID người dùng. |
| **Response** | Object người dùng (UserResource). |

---

## Tạo người dùng

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/users` |
| **Body** | `name` (required), `email` (required, unique), `password` (required, min 6, confirmed), `password_confirmation` (required), `status` (optional: active \| inactive \| banned), `assignments` (optional). |
| **Response** | 201, object người dùng + `"message": "Tài khoản đã được tạo thành công!"`. |

**Mẫu assignments**
```json
[
  { "role_id": 1, "organization_ids": [2, 3] },
  { "role_id": 5, "organization_ids": [9] }
]
```

---

## Cập nhật người dùng

| | |
|---|---|
| **Method** | PUT / PATCH |
| **Path** | `/api/users/{id}` |
| **Body** | `name`, `email` (unique nếu đổi), `password` (optional, min 6, confirmed), `password_confirmation`, `status`, `assignments` (optional). Khi gửi `assignments`, hệ thống đồng bộ lại toàn bộ phân quyền theo tổ chức của user. |
| **Response** | Object người dùng đã cập nhật. |

---

## Xóa người dùng

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/users/{id}` |
| **Response** | `{ "message": "Tài khoản đã được xóa thành công!" }`. |

---

## Xóa hàng loạt

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/users/bulk-delete` |
| **Body** | `ids` (array) — danh sách ID người dùng. |
| **Response** | `{ "message": "Đã xóa thành công các tài khoản được chọn!" }`. |

---

## Cập nhật trạng thái hàng loạt

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/users/bulk-status` |
| **Body** | `ids` (array), `status` (required: active \| inactive \| banned). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công" }`. |

---

## Đổi trạng thái người dùng

| | |
|---|---|
| **Method** | PATCH |
| **Path** | `/api/users/{id}/status` |
| **Body** | `status` (required: active \| inactive \| banned). |
| **Response** | `{ "message": "Cập nhật trạng thái thành công!", "data": UserResource }`. |

---

## Xuất Excel

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/users/export` |
| **Query** | Cùng bộ lọc với index: `search`, `status`, `from_date`, `to_date`, `sort_by`, `sort_order`. |
| **Response** | File `users.xlsx`. |

---

## Nhập Excel

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/users/import` |
| **Body** | `file` (required) — xlsx, xls, csv. Cột theo chuẩn export. |
| **Response** | `{ "message": "Users imported successfully." }`. |
