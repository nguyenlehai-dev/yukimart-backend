# API: Thiết lập giá (Price Lists)

**Base URL**: `/api/price-lists`
**Auth**: Bearer Token (required)
**Headers**: `X-Organization-Id` (required)

---

## 1. Danh sách bảng giá

**GET** `/api/price-lists`

| Param | Type | Mô tả |
|-------|------|-------|
| search | string | Tìm theo tên |
| status | string | `active`, `inactive` |
| is_active_now | boolean | Lọc bảng giá đang hiệu lực |
| sort_by | string | Trường sắp xếp |
| sort_order | string | `asc`, `desc` |
| limit | int | Số bản ghi/trang |

---

## 2. Chi tiết bảng giá

**GET** `/api/price-lists/{id}`

Trả về đầy đủ: thông tin bảng giá, items (kèm product, variant, unit), organizations, basePriceList.

---

## 3. Tạo bảng giá

**POST** `/api/price-lists`

```json
{
  "name": "Bảng giá sỉ",
  "description": "Giá dành cho khách mua sỉ",
  "status": "active",
  "start_date": "2026-01-01 00:00:00",
  "end_date": "2026-12-31 23:59:59",
  "base_price_list_id": 1,
  "formula_type": "percentage",
  "formula_value": -10,
  "auto_update_from_base": true,
  "add_products_from_base": true,
  "rounding_type": "thousand",
  "rounding_method": "round",
  "cashier_policy": "allow_with_warning",
  "organization_ids": [1, 2]
}
```

| Field | Type | Bắt buộc | Mô tả |
|-------|------|----------|-------|
| name | string | ✅ | Tên bảng giá |
| status | string | | `active`, `inactive` |
| start_date | datetime | | Ngày bắt đầu hiệu lực |
| end_date | datetime | | Ngày kết thúc hiệu lực |
| base_price_list_id | int | | ID bảng giá gốc |
| formula_type | string | | `percentage`, `fixed_amount` |
| formula_value | number | | VD: -10 = giảm 10%, +5000 = tăng 5000đ |
| auto_update_from_base | bool | | Tự động cập nhật theo bảng giá gốc |
| add_products_from_base | bool | | Thêm HH từ bảng giá gốc khi tạo |
| rounding_type | string | | `none`, `unit`, `ten`, `hundred`, `thousand`, `ten_thousand` |
| rounding_method | string | | `round`, `ceil`, `floor` |
| cashier_policy | string | | `allow_all`, `allow_with_warning`, `only_in_list` |
| organization_ids | array | | Danh sách ID chi nhánh áp dụng |

---

## 4. Cập nhật bảng giá

**PUT** `/api/price-lists/{id}`

Body tương tự tạo mới, các field là `sometimes`.

---

## 5. Xóa bảng giá

**DELETE** `/api/price-lists/{id}`

> Không thể xóa bảng giá mặc định.

---

## 6. Thêm/cập nhật sản phẩm vào bảng giá

**POST** `/api/price-lists/{id}/items`

```json
{
  "items": [
    {
      "product_id": 1,
      "variant_id": null,
      "unit_id": null,
      "price": 9000,
      "item_formula_type": "percentage",
      "item_formula_value": -10
    }
  ]
}
```

---

## 7. Xóa sản phẩm khỏi bảng giá

**DELETE** `/api/price-lists/{id}/items`

```json
{
  "item_ids": [1, 2, 3]
}
```

---

## 8. Thêm tất cả sản phẩm

**POST** `/api/price-lists/{id}/add-all`

Tự động thêm tất cả sản phẩm đang kinh doanh chưa có trong bảng giá.

---

## 9. Thêm theo nhóm hàng

**POST** `/api/price-lists/{id}/add-by-category`

```json
{
  "category_id": 3
}
```

---

## 10. Áp dụng công thức cho tất cả sản phẩm

**POST** `/api/price-lists/{id}/apply-formula`

```json
{
  "formula_type": "percentage",
  "formula_value": -10,
  "base_price_list_id": 1
}
```

---

## 11. So sánh nhiều bảng giá (tối đa 5)

**GET** `/api/price-lists/compare`

| Param | Type | Mô tả |
|-------|------|-------|
| price_list_ids[] | array | Danh sách ID bảng giá (tối đa 5) |
| search | string | Tìm sản phẩm theo tên/mã |
| category_id | int | Lọc theo nhóm hàng |

---

## 12. Xuất Excel bảng giá

**GET** `/api/price-lists/{id}/export`

Tải file `.xlsx` chứa danh sách sản phẩm và giá trong bảng giá.
