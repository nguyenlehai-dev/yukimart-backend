# Thiết kế cấu trúc dự án

Tài liệu mô tả cấu trúc thư mục hiện tại của hệ thống theo hướng modular.

## 1) Tổng quan thư mục gốc

```text
yukimart-backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
├── artisan
├── compose.yaml
├── composer.json
├── package.json
└── phpunit.xml
```

## 2) Cấu trúc module trong `app/Modules`

```text
app/Modules/
├── Auth/
│   ├── Requests/
│   ├── Routes/
│   └── Services/
└── Core/
    ├── Enums/
    ├── Exports/
    ├── Imports/
    ├── Middleware/
    ├── Models/
    ├── Requests/
    ├── Resources/
    ├── Routes/
    ├── Services/
    └── Traits/
```

Hiện tại dự án chỉ giữ lại nền tảng người dùng/phân quyền (Auth + Core). Các module nghiệp vụ
(Product, Purchase, Inventory, Document, Post, ShopAdmin) đã được gỡ bỏ để tối ưu và sẽ được
nhập lại bằng Excel hoặc dựng lại theo yêu cầu mới.

## 3) Quy ước luồng xử lý

- `Controller`: nhận request, gọi `FormRequest` validate, điều phối `Service`, trả response chuẩn.
- `Service`: xử lý nghiệp vụ và transaction.
- `Model`: định nghĩa quan hệ + scope filter/sort.
- `Resource`: chuẩn hóa output API.
- `Routes`: tách riêng theo module và resource.

## 4) Cơ sở dữ liệu

- DBMS: **PostgreSQL 17** (xem `compose.yaml`).
- Cấu hình kết nối: `config/database.php` (connection `pgsql`).
- Bảng còn lại sau khi reset:
  - `users`, `password_reset_tokens`, `personal_access_tokens` (Sanctum)
  - `organizations` (cấu trúc cây)
  - Spatie Permission: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
  - `log_activities` (nhật ký truy cập)
  - `media` (Spatie Media Library)
  - `settings` (cấu hình hệ thống)
  - `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`

## 5) Vị trí tài liệu liên quan

- Tài liệu API: `docs/api`.
- Phân tích nghiệp vụ/đề xuất: `docs/answer`.

## 6) Kiểm tra cập nhật tài liệu khi thay đổi kiến trúc

Khi thêm module mới hoặc thay đổi cấu trúc lớn, cần cập nhật đồng thời:

- `STRUCTURE_DESIGN.md` (file này).
- `docs/api/*.md` và tài liệu Scribe nếu thay đổi controller/endpoint API.
