# API Thương hiệu (Brand)

Quản lý thương hiệu sản phẩm.

**Base path:** `/api/brands`

---

## Danh sách

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/brands` |
| **Query** | `search`, `status`, `sort_by`, `sort_order`, `limit`. |
| **Response** | Paginated collection. |

---

## Dropdown options

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/brands/options` |
| **Response** | `[{ "id": 1, "name": "Samsung" }]` |

---

## Tạo mới

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/brands` |
| **Body** | `name` (required), `description` (optional), `status` (active \| inactive). |
| **Response** | 201, object thương hiệu. |

---

## Cập nhật

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/brands/{id}` |
| **Body** | Giống tạo. |
| **Response** | Object thương hiệu đã cập nhật. |

---

## Xóa

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/brands/{id}` |
| **Response** | `"message": "Đã xóa thương hiệu."`. Lỗi 422 nếu đang có sản phẩm. |
