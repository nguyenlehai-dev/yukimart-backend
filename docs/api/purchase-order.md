# API: Nhap hang (Purchase Orders)

**Base URL**: `/api/purchase-orders`
**Auth**: Bearer Token (required)
**Headers**: `X-Organization-Id` (required)

---

## 1. Danh sach phieu nhap hang

**GET** `/api/purchase-orders`

| Param | Type | Mo ta |
|-------|------|-------|
| search | string | Tim theo ma phieu |
| supplier_id | int | Loc theo NCC |
| status | string | `draft`, `completed`, `cancelled` |
| organization_id | int | Loc theo chi nhanh |
| from_date / to_date | date | Khoang thoi gian |

---

## 2. Chi tiet phieu nhap

**GET** `/api/purchase-orders/{id}`

Tra ve: thong tin phieu, items (product/variant/unit), supplier, returns lien quan.

---

## 3. Tao phieu nhap hang

**POST** `/api/purchase-orders`

```json
{
  "supplier_id": 1,
  "organization_id": 1,
  "status": "completed",
  "discount": 10000,
  "paid_amount": 50000,
  "note": "Nhap lo hang thang 3",
  "order_date": "2026-03-24",
  "items": [
    {
      "product_id": 1,
      "variant_id": null,
      "unit_id": null,
      "quantity": 100,
      "price": 7500,
      "discount": 0
    }
  ]
}
```

- `status = completed`: tu dong cong ton kho + ghi nhan cong no NCC
- `status = draft`: luu tam, chua cong ton kho

---

## 4. Cap nhat phieu (NCC, ghi chu, ngay)

**PUT** `/api/purchase-orders/{id}`

```json
{
  "supplier_id": 2,
  "note": "Ghi chu moi",
  "order_date": "2026-03-25"
}
```

---

## 5. Mo phieu (de sua toan bo)

**POST** `/api/purchase-orders/{id}/reopen`

> Chuyen completed → draft. He thong tu dong tru ton kho + hoan cong no NCC.

---

## 6. Hoan thanh phieu tam

**POST** `/api/purchase-orders/{id}/complete`

> Chuyen draft → completed. He thong tu dong cong ton kho + ghi nhan cong no.

---

## 7. Huy phieu nhap

**POST** `/api/purchase-orders/{id}/cancel`

> Tru ton kho + hoan cong no NCC. Phieu chuyen thanh `cancelled`.

---

## 8. Sao chep phieu

**POST** `/api/purchase-orders/{id}/copy`

> Tao phieu moi (draft) tu phieu cu.

---

## 9. Xuat Excel

**GET** `/api/purchase-orders/export`

---

# API: Nha cung cap (Suppliers)

**Base URL**: `/api/suppliers`

## 1. Danh sach NCC
**GET** `/api/suppliers`

## 2. Options (dropdown)
**GET** `/api/suppliers/options`

## 3. Chi tiet
**GET** `/api/suppliers/{id}`

## 4. Tao NCC
**POST** `/api/suppliers`

```json
{
  "name": "NCC ABC",
  "phone": "0901234567",
  "email": "ncc@abc.com",
  "tax_code": "123456789",
  "address": "123 Nguyen Hue, Q1"
}
```

## 5. Cap nhat
**PUT** `/api/suppliers/{id}`

## 6. Xoa
**DELETE** `/api/suppliers/{id}`
