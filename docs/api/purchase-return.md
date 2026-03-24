# API: Tra hang nhap (Purchase Returns)

**Base URL**: `/api/purchase-returns`
**Auth**: Bearer Token (required)
**Headers**: `X-Organization-Id` (required)

---

## 1. Danh sach phieu tra hang nhap

**GET** `/api/purchase-returns`

| Param | Type | Mo ta |
|-------|------|-------|
| search | string | Tim theo ma phieu |
| supplier_id | int | Loc theo NCC |
| status | string | `completed`, `cancelled` |
| organization_id | int | Loc theo chi nhanh |
| purchase_order_id | int | Loc theo phieu nhap |
| from_date | date | Tu ngay |
| to_date | date | Den ngay |
| sort_by | string | Truong sap xep |
| sort_order | string | `asc`, `desc` |

---

## 2. Chi tiet phieu tra hang nhap

**GET** `/api/purchase-returns/{id}`

---

## 3. Tra hang nhap nhanh

**POST** `/api/purchase-returns/quick`

```json
{
  "supplier_id": 1,
  "organization_id": 1,
  "supplier_paid": 50000,
  "note": "Hang loi, tra NCC",
  "return_date": "2026-03-24",
  "items": [
    {
      "product_id": 1,
      "variant_id": null,
      "unit_id": null,
      "quantity": 5,
      "price": 10000
    }
  ]
}
```

---

## 4. Tra hang nhap theo phieu nhap hang

**POST** `/api/purchase-returns/from-order`

```json
{
  "purchase_order_id": 1,
  "organization_id": 1,
  "supplier_paid": 0,
  "note": "Tra theo phieu nhap NH000001",
  "items": [
    {
      "product_id": 1,
      "quantity": 3,
      "price": 10000
    }
  ]
}
```

---

## 5. Cap nhat phieu (ghi chu, ngay tra)

**PUT** `/api/purchase-returns/{id}`

```json
{
  "note": "Ghi chu moi",
  "return_date": "2026-03-25"
}
```

---

## 6. Huy phieu tra hang nhap

**POST** `/api/purchase-returns/{id}/cancel`

> He thong tu dong cong lai ton kho va cap nhat lai cong no NCC.

---

## 7. Sao chep phieu

**POST** `/api/purchase-returns/{id}/copy`

---

## 8. Xuat Excel

**GET** `/api/purchase-returns/export`

Tat ca filter param cua danh sach deu ap dung duoc.
