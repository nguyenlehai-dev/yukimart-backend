# API: Nha cung cap (Suppliers)

**Base URL**: `/api/suppliers`
**Auth**: Bearer Token (required)
**Headers**: `X-Organization-Id` (required)

---

## CRUD

### 1. Danh sach NCC
**GET** `/api/suppliers`

| Param | Type | Mo ta |
|-------|------|-------|
| search | string | Tim theo ten/ma/SDT/cong ty |
| status | string | `active`, `inactive` |
| group_id | int | Loc theo nhom NCC |
| organization_id | int | Loc theo chi nhanh |
| has_debt | bool | Chi NCC co cong no |

### 2. Options (dropdown)
**GET** `/api/suppliers/options`

### 3. Chi tiet
**GET** `/api/suppliers/{id}`

### 4. Tao NCC
**POST** `/api/suppliers`

```json
{
  "name": "NCC ABC",
  "company": "Cong ty ABC",
  "phone": "0901234567",
  "email": "ncc@abc.com",
  "tax_code": "123456789",
  "fax": "028123456",
  "website": "https://abc.com",
  "address": "123 Nguyen Hue, Q1",
  "group_id": 1,
  "organization_id": 1,
  "note": "Ghi chu"
}
```

### 5. Cap nhat
**PUT** `/api/suppliers/{id}`

### 6. Xoa (danh dau DEL)
**DELETE** `/api/suppliers/{id}`

### 7. Xoa hang loat
**POST** `/api/suppliers/bulk-delete`
```json
{ "ids": [1, 2, 3] }
```

### 8. Ngung/Cho phep hoat dong
**POST** `/api/suppliers/{id}/toggle-status`

---

## Lich su giao dich

### 9. Lich su nhap/tra hang
**GET** `/api/suppliers/{id}/transactions`

| Param | Type | Mo ta |
|-------|------|-------|
| from_date | date | Tu ngay |
| to_date | date | Den ngay |

---

## Cong no

### 10. Lich su cong no
**GET** `/api/suppliers/{id}/debt`

### 11. Thanh toan cong no
**POST** `/api/suppliers/{id}/pay-debt`

```json
{
  "amount": 500000,
  "payment_method": "cash",
  "purchase_order_id": 1,
  "note": "Thanh toan dot 1",
  "transaction_date": "2026-03-24"
}
```

### 12. Chiet khau thanh toan
**POST** `/api/suppliers/{id}/discount`

```json
{
  "amount": 100000,
  "purchase_order_id": 1,
  "note": "Chiet khau thanh toan som"
}
```

### 13. Dieu chinh cong no
**POST** `/api/suppliers/{id}/adjust-debt`

```json
{
  "new_debt": 1500000,
  "note": "Dieu chinh theo doi soat",
  "transaction_date": "2026-03-24"
}
```

---

## Nhom NCC

### 14. Danh sach nhom
**GET** `/api/suppliers/groups`

### 15. Tao nhom
**POST** `/api/suppliers/groups`
```json
{ "name": "NCC trong nuoc", "description": "Mo ta" }
```

### 16. Cap nhat nhom
**PUT** `/api/suppliers/groups/{groupId}`

### 17. Xoa nhom
**DELETE** `/api/suppliers/groups/{groupId}`

---

## Import / Export

### 18. Xuat Excel
**GET** `/api/suppliers/export`

### 19. Import Excel
**POST** `/api/suppliers/import`
(form-data: file)
