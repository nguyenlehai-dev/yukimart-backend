# API Nhóm hàng (Product Category)

Quản lý nhóm hàng hóa phân cấp (cây cha-con).

**Base path:** `/api/product-categories`

---

## Danh sách nhóm hàng

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/product-categories` |
| **Query** | `search`, `status` (active \| inactive), `parent_id`, `sort_by` (id \| name \| sort_order \| created_at), `sort_order` (asc \| desc), `limit`. |
| **Response** | Paginated collection. |

---

## Cây nhóm hàng

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/product-categories/tree` |
| **Response** | Mảng cây phân cấp (children lồng nhau), chỉ nhóm active. |

---

## Dropdown options

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/product-categories/options` |
| **Response** | `[{ "id": 1, "name": "Thực phẩm", "parent_id": null }]` |

---

## Chi tiết

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/product-categories/{id}` |
| **Response** | Object ProductCategoryResource. |

---

## Tạo nhóm hàng

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/product-categories` |
| **Body** | `name` (required), `description` (optional), `status` (active \| inactive), `parent_id` (optional, ID nhóm cha), `sort_order` (optional). |
| **Response** | 201, object nhóm hàng + `"message": "Tạo nhóm hàng thành công."`. |

---

## Cập nhật

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/product-categories/{id}` |
| **Body** | Giống tạo (các field tùy chọn). |
| **Response** | Object nhóm hàng đã cập nhật. |

---

## Xóa

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/product-categories/{id}` |
| **Response** | `"message": "Đã xóa nhóm hàng."`. Lỗi 422 nếu nhóm đang chứa sản phẩm hoặc nhóm con. |
