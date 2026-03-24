# Thiết kế cấu trúc dự án

Tài liệu mô tả cấu trúc thư mục hiện tại của hệ thống theo hướng modular.

## 1) Tổng quan thư mục gốc

```text
hailn-core/
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
├── Core/
│   ├── Enums/
│   ├── Exports/
│   ├── Imports/
│   ├── Middleware/
│   ├── Models/
│   ├── Requests/
│   ├── Resources/
│   ├── Routes/
│   ├── Services/
│   └── Traits/
├── Post/
│   ├── Enums/
│   ├── Exports/
│   ├── Imports/
│   ├── Models/
│   ├── Requests/
│   ├── Resources/
│   ├── Routes/
│   └── Services/
└── Document/
    ├── Controllers/
    ├── Enums/
    ├── Exports/
    ├── Imports/
    ├── Models/
    ├── Requests/
    ├── Resources/
    ├── Routes/
    └── Services/
└── Product/
    ├── Controllers/
    ├── Enums/
    ├── Exports/
    ├── Imports/
    ├── Models/
    ├── Requests/
    ├── Resources/
    ├── Routes/
    └── Services/
```

## 3) Quy ước luồng xử lý

- `Controller`: nhận request, gọi `FormRequest` validate, điều phối `Service`, trả response chuẩn.
- `Service`: xử lý nghiệp vụ và transaction.
- `Model`: định nghĩa quan hệ + scope filter/sort.
- `Resource`: chuẩn hóa output API.
- `Routes`: tách riêng theo module và resource.

## 4) Vị trí tài liệu liên quan

- Tài liệu API: `docs/api`.
- Phân tích nghiệp vụ/đề xuất: `docs/answer`.
- Thiết kế cơ sở dữ liệu: `docs/DATABASE_DESIGN.md`.

## 5) Kiểm tra cập nhật tài liệu khi thay đổi kiến trúc

Khi thêm module mới hoặc thay đổi cấu trúc lớn, cần cập nhật đồng thời:

- `STRUCTURE_DESIGN.md` (file này).
- `docs/DATABASE_DESIGN.md` nếu có migration mới.
- `docs/api/*.md` và tài liệu Scribe nếu thay đổi controller/endpoint API.
