# API Document

## Base path
- `/api/documents`

## Endpoints

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/documents/stats` | Thống kê văn bản |
| GET | `/api/documents` | Danh sách văn bản (filter/sort/paginate) |
| GET | `/api/documents/{document}` | Chi tiết văn bản |
| POST | `/api/documents` | Tạo văn bản mới |
| PUT/PATCH | `/api/documents/{document}` | Cập nhật văn bản |
| DELETE | `/api/documents/{document}` | Xóa văn bản |
| POST | `/api/documents/bulk-delete` | Xóa hàng loạt |
| PATCH | `/api/documents/bulk-status` | Cập nhật trạng thái hàng loạt |
| PATCH | `/api/documents/{document}/status` | Đổi trạng thái |
| GET | `/api/documents/export` | Xuất Excel |
| POST | `/api/documents/import` | Nhập Excel |

## Request body chính (store/update)

```json
{
  "so_ky_hieu": "VB-01/2026",
  "ten_van_ban": "Quyết định ban hành quy chế",
  "noi_dung": "Nội dung văn bản...",
  "issuing_agency_id": 1,
  "issuing_level_id": 1,
  "signer_id": 1,
  "document_type_ids": [1, 2],
  "document_field_ids": [1, 3],
  "ngay_ban_hanh": "2026-02-21",
  "ngay_xuat_ban": "2026-02-22",
  "ngay_hieu_luc": "2026-03-01",
  "ngay_het_hieu_luc": "2026-12-31",
  "status": "active"
}
```

`attachments[]` gửi dạng multipart/form-data để đính kèm nhiều file.
