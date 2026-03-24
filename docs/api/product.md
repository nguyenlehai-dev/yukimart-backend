# API Hàng hóa (Product)

Quản lý sản phẩm: 4 loại (hàng hóa, dịch vụ, combo, hàng sản xuất). Hỗ trợ biến thể, thành phần combo, đa đơn vị tính, ảnh, tồn kho, thẻ kho, sao chép, ngừng kinh doanh, thao tác hàng loạt, xuất/nhập Excel.

**Base path:** `/api/products`

---

## Danh sách hàng hóa

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products` |
| **Query** | `search` (tên, mã, barcode), `type` (product \| service \| combo \| manufacturing), `category_id`, `brand_id`, `is_active` (true \| false), `status` (active \| inactive \| discontinued), `sort_by` (id \| name \| code \| base_price \| cost_price \| created_at \| updated_at), `sort_order`, `limit`. |
| **Response** | Paginated collection kèm category, brand, baseUnit, variants count, total inventory. |

---

## Chi tiết hàng hóa

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products/{id}` |
| **Response** | Object đầy đủ: thông tin sản phẩm, variants (kèm attribute values), components (combo/sản xuất), locations, unit conversions, images. |

---

## Tạo hàng hóa

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/products` |
| **Body** | `name` (required), `type` (required: product \| service \| combo \| manufacturing), `category_id`, `brand_id`, `base_unit_id`, `base_price`, `cost_price`, `barcode`, `description`, `weight`, `allow_negative_stock`, `min_stock`, `max_stock`, `point`, `location_ids[]`, `images[]` (file upload, tối đa 10 ảnh), `variants[]` (mảng biến thể), `components[]` (mảng thành phần cho combo/sản xuất), `unit_conversions[]` (quy đổi ĐVT). |
| **Response** | 201, object đầy đủ + `"message": "Tạo hàng hóa thành công."`. |

---

## Cập nhật hàng hóa

| | |
|---|---|
| **Method** | PUT / POST (form-data) |
| **Path** | `/api/products/{id}` |
| **Body** | Giống tạo. Thêm: `remove_image_ids[]` (xóa ảnh cũ). |
| **Response** | Object đã cập nhật. |

---

## Xóa hàng hóa

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/products/{id}` |
| **Response** | `"message": "Đã xóa hàng hóa."`. Lỗi 422 nếu tồn kho ≠ 0. |

---

## Sao chép hàng hóa

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/products/{id}/copy` |
| **Response** | 201, object sản phẩm mới (code tự sinh, tên thêm " (copy)"). |

---

## Ngừng / Cho phép kinh doanh

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/products/{id}/toggle-active` |
| **Response** | Object + `"message": "Đã cập nhật trạng thái kinh doanh."`. |

---

## Batch: Ngừng/Cho phép kinh doanh hàng loạt

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/products/bulk-toggle-active` |
| **Body** | `ids[]` (mảng ID), `is_active` (true \| false). |
| **Response** | `"message": "Đã cập nhật N sản phẩm."`. |

---

## Batch: Đổi nhóm hàng

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/products/bulk-category` |
| **Body** | `ids[]`, `category_id`. |
| **Response** | `"message": "Đã đổi nhóm hàng N sản phẩm."`. |

---

## Batch: Thiết lập điểm

| | |
|---|---|
| **Method** | PUT |
| **Path** | `/api/products/bulk-point` |
| **Body** | `ids[]`, `point` (integer). |
| **Response** | `"message": "Đã thiết lập điểm N sản phẩm."`. |

---

## Batch: Xóa hàng loạt

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/products/bulk-delete` |
| **Body** | `ids[]`. |
| **Response** | `"message": "Đã xóa N sản phẩm."`. |

---

## Xuất Excel

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products/export` |
| **Query** | Cùng bộ lọc với index. Hoặc `ids[]` để xuất chọn lọc. |
| **Response** | File `products.xlsx`. |

---

## Nhập Excel

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/products/import` |
| **Body** | `file` (required, xlsx/xls/csv). |
| **Response** | `"message": "Import thành công N sản phẩm."`. |

---

## Tồn kho theo chi nhánh

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products/{id}/inventory` |
| **Response** | Mảng `[{ "organization_id": 1, "organization_name": "...", "quantity": 100, "cost_price": 15000 }]`. |

---

## Thẻ kho (lịch sử giao dịch)

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products/{id}/stock-card` |
| **Query** | `organization_id`, `from_date`, `to_date`, `type` (import \| export \| sale \| return \| adjust \| transfer). |
| **Response** | Paginated list lịch sử thay đổi tồn kho. |

---

## Phân tích hiệu quả kinh doanh

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/products/{id}/analytics` |
| **Query** | `from_date`, `to_date`, `organization_id`. |
| **Response** | Object: doanh thu, lợi nhuận, số lượng bán, giá trị trả hàng. |

---

## Response mẫu (ProductResource)

```json
{
  "id": 1,
  "code": "SP000001",
  "barcode": "8935001730115",
  "name": "Nước ngọt Coca Cola 330ml",
  "slug": "nuoc-ngot-coca-cola-330ml",
  "type": "product",
  "category": { "id": 2, "name": "Đồ uống" },
  "brand": { "id": 1, "name": "Coca Cola" },
  "base_unit": { "id": 2, "name": "Lon" },
  "base_price": 10000,
  "cost_price": 7500,
  "weight": 330,
  "is_active": true,
  "status": "active",
  "point": 1,
  "variants": [],
  "components": [],
  "locations": [{ "id": 1, "name": "Kệ A1" }],
  "images": [],
  "total_inventory": 500,
  "created_at": "2026-03-24T09:00:00.000Z",
  "updated_at": "2026-03-24T09:00:00.000Z"
}
```
