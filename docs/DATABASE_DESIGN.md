# Sơ đồ thiết kế cơ sở dữ liệu

Tài liệu mô tả chi tiết cấu trúc các bảng trong hệ thống, đồng bộ với migration Laravel.

---

## 1. Người dùng & xác thực

### `users`
Bảng người dùng (Laravel Auth).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| email | varchar(255) | No | — | UNIQUE |
| user_name | varchar(100) | Yes | null | UNIQUE, dùng để đăng nhập cùng email |
| email_verified_at | timestamp | Yes | null | |
| password | varchar(255) | No | — | |
| remember_token | varchar(100) | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive, banned |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `password_reset_tokens`
Token đặt lại mật khẩu.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| email | varchar(255) | No | — | PK |
| token | varchar(255) | No | — | |
| created_at | timestamp | Yes | null | |

### `sessions`
Phiên đăng nhập (session).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | varchar(255) | No | — | PK |
| user_id | bigint unsigned | Yes | null | INDEX |
| ip_address | varchar(45) | Yes | null | |
| user_agent | text | Yes | null | |
| payload | longtext | No | — | |
| last_activity | int | No | — | INDEX |

### `personal_access_tokens`
Token API (Sanctum): tokenable_type, tokenable_id (morphs).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| tokenable_type | varchar(255) | No | — | Polymorphic |
| tokenable_id | bigint unsigned | No | — | Polymorphic, INDEX |
| name | text | No | — | |
| token | varchar(64) | No | — | UNIQUE |
| abilities | text | Yes | null | |
| last_used_at | timestamp | Yes | null | |
| expires_at | timestamp | Yes | null | INDEX |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

---

## 2. Cache & Queue (Laravel)

### `cache`
Cache key-value.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| key | varchar(255) | No | — | PK |
| value | mediumtext | No | — | |
| expiration | int | No | — | INDEX |

### `cache_locks`
Lock cho cache.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| key | varchar(255) | No | — | PK |
| owner | varchar(255) | No | — | |
| expiration | int | No | — | INDEX |

### `jobs`
Hàng đợi job.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| queue | varchar(255) | No | — | INDEX |
| payload | longtext | No | — | |
| attempts | tinyint unsigned | No | — | |
| reserved_at | int unsigned | Yes | null | |
| available_at | int unsigned | No | — | |
| created_at | int unsigned | No | — | |

### `job_batches`
Batch job (queue batching).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | varchar(255) | No | — | PK |
| name | varchar(255) | No | — | |
| total_jobs | int | No | — | |
| pending_jobs | int | No | — | |
| failed_jobs | int | No | — | |
| failed_job_ids | longtext | No | — | |
| options | mediumtext | Yes | null | |
| cancelled_at | int | Yes | null | |
| created_at | int | No | — | |
| finished_at | int | Yes | null | |

### `failed_jobs`
Job thất bại.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| uuid | varchar(255) | No | — | UNIQUE |
| connection | text | No | — | |
| queue | text | No | — | |
| payload | longtext | No | — | |
| exception | longtext | No | — | |
| failed_at | timestamp | No | current | |

---

## 3. Core – Permission, Role, Organization (Spatie Laravel Permission)

### `organizations`
Bảng tổ chức (organization) dùng cho Spatie Laravel Permission; cấu trúc cây theo parent_id.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| slug | varchar(255) | Yes | null | UNIQUE |
| description | text | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive |
| parent_id | bigint unsigned | Yes | null | FK → organizations.id (cha) |
| sort_order | int unsigned | No | 0 | Thứ tự trong cây |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `permissions`
Quyền (Spatie Laravel Permission). Bổ sung description, sort_order, parent_id để nhóm và sắp xếp hiển thị frontend.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | UNIQUE(name, guard_name) |
| guard_name | varchar(255) | No | — | |
| description | text | Yes | null | Mô tả hiển thị frontend |
| sort_order | int unsigned | No | 0 | Thứ tự sắp xếp |
| parent_id | bigint unsigned | Yes | null | FK → permissions.id (nhóm cấp cha) |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `roles`
Vai trò (Spatie Laravel Permission, bật teams/organizations). Cấu trúc mặc định Spatie, không có cột status.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| organization_id | bigint unsigned | Yes | null | FK → organizations.id (ngữ cảnh organization) |
| name | varchar(255) | No | — | UNIQUE(organization_id, name, guard_name) |
| guard_name | varchar(255) | No | — | |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `model_has_permissions`
Pivot: model (user) ↔ permission (Spatie, bật organizations).

| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| permission_id | bigint unsigned | FK → permissions.id |
| model_type | varchar(255) | Polymorphic |
| model_id | bigint unsigned | Polymorphic |
| organization_id | bigint unsigned | FK organization (khi bật teams) |
| — | — | PK(organization_id, permission_id, model_id, model_type) |

### `model_has_roles`
Pivot: model (user) ↔ role (Spatie, bật organizations).

| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| role_id | bigint unsigned | FK → roles.id |
| model_type | varchar(255) | Polymorphic |
| model_id | bigint unsigned | Polymorphic |
| organization_id | bigint unsigned | FK organization (khi bật teams) |
| — | — | PK(organization_id, role_id, model_id, model_type) |

### `role_has_permissions`
Pivot: role ↔ permission (Spatie).

| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| permission_id | bigint unsigned | FK → permissions.id |
| role_id | bigint unsigned | FK → roles.id |
| — | — | PK(permission_id, role_id) |

### `log_activities`
Nhật ký truy cập của người dùng.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| description | varchar(255) | No | — | Mô tả hành động (vd: Xem chi tiết bài viết #10) |
| user_type | varchar(255) | No | 'Guest' | Loại user (Guest, User, ...) |
| user_id | bigint unsigned | Yes | null | FK → users.id |
| organization_id | bigint unsigned | Yes | null | FK → organizations.id |
| route | varchar(255) | No | — | URL đầy đủ |
| method_type | varchar(255) | No | — | GET, POST, PUT, ... |
| status_code | int | No | — | 200, 400, 500, ... |
| ip_address | varchar(255) | No | — | |
| country | varchar(255) | Yes | null | |
| user_agent | text | Yes | null | |
| request_data | json | Yes | null | Dữ liệu request (đã loại trừ password, token) |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

**Quan hệ:** belongsTo user, organization. Index: user_id+created_at, organization_id+created_at, created_at.

### `settings`
Bảng cấu hình hệ thống (key-value): thông tin chung, trang quản trị, trang chọn tổ chức, mạng xã hội, API, nhật ký.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| key | varchar(255) | No | — | UNIQUE |
| value | text | Yes | null | Giá trị cấu hình |
| group | varchar(100) | No | 'general' | general, admin_page, org_select_page, social, api, email, sms, zalo, chat, log |
| is_public | boolean | No | true | true = trả về khi gọi API công khai |
| type | varchar(50) | No | 'string' | string, text, integer, boolean, json |
| label | varchar(255) | Yes | null | Nhãn hiển thị tiếng Việt |
| sort_order | int unsigned | No | 0 | Thứ tự hiển thị trong nhóm |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

**Quan hệ:** belongsTo creator, editor (User). Chi tiết các key mặc định và API xem `/docs/answer/phan-tich-bang-cau-hinh.md`.

---

## 4. Bài viết & Danh mục (Module Post)

### `posts`
Bài viết tin tức.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| title | varchar(255) | No | — | |
| content | text | No | — | |
| status | varchar(255) | No | 'draft' | draft, published, archived |
| view_count | int unsigned | No | 0 | Lượt xem |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

**Quan hệ:**  
- N-n với `post_categories` qua bảng `post_post_category`.  
- 1-n (polymorphic) với `media` qua Spatie Media Library (`model_type = App\Modules\Post\Models\Post`, `collection_name = post-attachments`).

### `media`
Bảng media dùng chung từ Spatie Media Library (quản lý file polymorphic cho nhiều model).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| model_type | varchar(255) | No | — | Polymorphic type |
| model_id | bigint unsigned | No | — | Polymorphic id |
| uuid | char(36) | Yes | null | UNIQUE |
| collection_name | varchar(255) | No | — | Ví dụ: `post-attachments` |
| name | varchar(255) | No | — | Tên hiển thị |
| file_name | varchar(255) | No | — | Tên file lưu trên disk |
| mime_type | varchar(255) | Yes | null | |
| disk | varchar(255) | No | — | Disk lưu trữ (`public`) |
| conversions_disk | varchar(255) | Yes | null | |
| size | bigint unsigned | No | — | Kích thước (bytes) |
| manipulations | json | No | — | |
| custom_properties | json | No | — | Lưu metadata (vd `original_name`) |
| generated_conversions | json | No | — | |
| responsive_images | json | No | — | |
| order_column | int unsigned | Yes | null | Thứ tự trong collection, có index |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `post_categories`
Danh mục tin tức phân cấp (cây theo parent_id).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| slug | varchar(255) | No | — | UNIQUE |
| description | text | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive |
| sort_order | int unsigned | No | 0 | |
| parent_id | bigint unsigned | Yes | null | FK → post_categories.id (cha) |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

**Quan hệ:** Cây parent_id; N-n với `posts` qua bảng `post_post_category`.

### `post_post_category`
Bảng pivot: bài viết ↔ danh mục (n-n).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| post_id | bigint unsigned | No | — | FK → posts.id, ON DELETE CASCADE |
| post_category_id | bigint unsigned | No | — | FK → post_categories.id, ON DELETE CASCADE |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |
| — | — | — | — | UNIQUE(post_id, post_category_id) |

---

## Sơ đồ quan hệ (Module Post)

```
users ──┬── created_by/updated_by ──► posts
        │                                    ├── 1-n (polymorphic) ──► media
        │                                    └── n-n ──► post_post_category ◄── n-n ── post_categories
        └── created_by/updated_by ──► post_categories
```

---

## 5. Văn bản & Danh mục (Module Document)

### `documents`
Bảng văn bản chính.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| so_ky_hieu | varchar(255) | No | — | UNIQUE |
| ten_van_ban | varchar(255) | No | — | |
| noi_dung | longtext | Yes | null | |
| issuing_agency_id | bigint unsigned | Yes | null | FK → document_issuing_agencies.id |
| issuing_level_id | bigint unsigned | Yes | null | FK → document_issuing_levels.id |
| signer_id | bigint unsigned | Yes | null | FK → document_signers.id |
| ngay_ban_hanh | date | Yes | null | |
| ngay_xuat_ban | date | Yes | null | |
| ngay_hieu_luc | date | Yes | null | |
| ngay_het_hieu_luc | date | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### Bảng danh mục

Các bảng: `document_types`, `document_issuing_agencies`, `document_issuing_levels`, `document_signers`, `document_fields` có cùng cấu trúc:

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| description | text | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### Pivot module document

#### `document_document_type`
| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| document_id | bigint unsigned | No | — | FK → documents.id, ON DELETE CASCADE |
| document_type_id | bigint unsigned | No | — | FK → document_types.id, ON DELETE CASCADE |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |
| — | — | — | — | UNIQUE(document_id, document_type_id) |

#### `document_document_field`
| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| document_id | bigint unsigned | No | — | FK → documents.id, ON DELETE CASCADE |
| document_field_id | bigint unsigned | No | — | FK → document_fields.id, ON DELETE CASCADE |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |
| — | — | — | — | UNIQUE(document_id, document_field_id) |

**Quan hệ:**  
- `documents` n-1 với `document_issuing_agencies`, `document_issuing_levels`, `document_signers`.  
- `documents` n-n với `document_types` và `document_fields`.  
- `documents` 1-n (polymorphic) với `media` qua `collection_name = document-attachments`.

---

## 6. Hàng hóa & Tồn kho (Module Product)

### `product_categories`
Nhóm hàng hóa phân cấp (cây theo parent_id).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| slug | varchar(255) | No | — | UNIQUE |
| description | text | Yes | null | |
| status | varchar(255) | No | 'active' | active, inactive |
| parent_id | bigint unsigned | Yes | null | FK → product_categories.id (cha) |
| sort_order | int unsigned | No | 0 | |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| updated_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |
| updated_at | timestamp | Yes | null | |

### `brands`
Thương hiệu sản phẩm.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| slug | varchar(255) | No | — | UNIQUE |
| description | text | Yes | null | |
| status | varchar(255) | No | 'active' | |
| created_by / updated_by | bigint unsigned | Yes | null | FK → users.id |
| timestamps | | | | |

### `locations`
Vị trí lưu trữ/trưng bày sản phẩm.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| description | text | Yes | null | |
| organization_id | bigint unsigned | Yes | null | FK → organizations.id (chi nhánh) |
| status | varchar(255) | No | 'active' | |
| created_by / updated_by | bigint unsigned | Yes | null | FK → users.id |
| timestamps | | | | |

### `product_attributes`
Thuộc tính sản phẩm (Size, Màu sắc, Chất liệu...).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(255) | No | — | |
| sort_order | int unsigned | No | 0 | |
| timestamps | | | | |

### `product_attribute_values`
Giá trị thuộc tính (S, M, L, Đỏ, Xanh...).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| attribute_id | bigint unsigned | No | — | FK → product_attributes.id, CASCADE |
| value | varchar(255) | No | — | |
| sort_order | int unsigned | No | 0 | |
| timestamps | | | | |

### `product_units`
Đơn vị tính sản phẩm.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| name | varchar(100) | No | — | Cái, Chai, Thùng, Kg... |
| timestamps | | | | |

### `products`
Bảng sản phẩm chính. Hỗ trợ 4 loại: product, service, combo, manufacturing.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| code | varchar(50) | No | — | UNIQUE, auto-gen (SP000001, DV000001, CB000001, SX000001) |
| barcode | varchar(100) | Yes | null | UNIQUE |
| name | varchar(255) | No | — | |
| slug | varchar(255) | No | — | UNIQUE |
| description | text | Yes | null | |
| type | varchar(255) | No | 'product' | product, service, combo, manufacturing |
| category_id | bigint unsigned | Yes | null | FK → product_categories.id |
| brand_id | bigint unsigned | Yes | null | FK → brands.id |
| base_unit_id | bigint unsigned | Yes | null | FK → product_units.id (ĐVT cơ bản) |
| base_price | decimal(15,2) | No | 0 | Giá bán |
| cost_price | decimal(15,2) | No | 0 | Giá vốn |
| weight | decimal(10,3) | Yes | null | Trọng lượng (g) |
| allow_negative_stock | boolean | No | false | Cho phép tồn kho âm |
| min_stock | int | No | 0 | Tồn kho tối thiểu |
| max_stock | int | Yes | null | Tồn kho tối đa |
| status | varchar(255) | No | 'active' | active, inactive, discontinued |
| is_active | boolean | No | true | Đang kinh doanh |
| point | int | No | 0 | Điểm tích lũy |
| created_by / updated_by | bigint unsigned | Yes | null | FK → users.id |
| timestamps | | | | |

**Index:** type, is_active, (category_id + is_active).

### `product_variants`
Phiên bản sản phẩm (theo thuộc tính: size, màu...).

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK, auto increment |
| product_id | bigint unsigned | No | — | FK → products.id, CASCADE |
| sku | varchar(100) | No | — | UNIQUE |
| barcode | varchar(100) | Yes | null | UNIQUE |
| price | decimal(15,2) | No | 0 | Giá bán (override) |
| cost_price | decimal(15,2) | No | 0 | |
| name | varchar(255) | No | — | VD: "Đỏ - XL" |
| is_active | boolean | No | true | |
| timestamps | | | | |

### `product_variant_attributes`
Pivot: variant ↔ attribute_value.

| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| id | bigint unsigned | PK |
| variant_id | bigint unsigned | FK → product_variants.id, CASCADE |
| attribute_value_id | bigint unsigned | FK → product_attribute_values.id, CASCADE |
| — | — | UNIQUE(variant_id, attribute_value_id) |

### `product_components`
Thành phần của Combo / Hàng sản xuất.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK |
| parent_product_id | bigint unsigned | No | — | FK → products.id, CASCADE |
| component_product_id | bigint unsigned | No | — | FK → products.id, CASCADE |
| quantity | decimal(10,3) | No | — | Số lượng |
| unit_id | bigint unsigned | Yes | null | FK → product_units.id |
| timestamps | | | | |
| — | — | — | — | UNIQUE(parent_product_id, component_product_id) |

### Pivot tables

#### `product_location`
| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| product_id | bigint unsigned | FK → products.id, CASCADE |
| location_id | bigint unsigned | FK → locations.id, CASCADE |
| — | — | PK(product_id, location_id) |

#### `product_product_unit`
Quy đổi đơn vị tính (VD: 1 thùng = 24 chai).

| Cột | Kiểu | Ràng buộc / Ghi chú |
|-----|------|---------------------|
| id | bigint unsigned | PK |
| product_id | bigint unsigned | FK → products.id, CASCADE |
| unit_id | bigint unsigned | FK → product_units.id, CASCADE |
| conversion_value | decimal(10,3) | Hệ số quy đổi (default 1) |
| price | decimal(15,2) nullable | Giá bán theo ĐVT |
| barcode | varchar(100) nullable | Mã vạch theo ĐVT |
| — | — | UNIQUE(product_id, unit_id) |

### `inventory`
Tồn kho theo chi nhánh (organization) + variant.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK |
| product_id | bigint unsigned | No | — | FK → products.id, CASCADE |
| variant_id | bigint unsigned | Yes | null | FK → product_variants.id, CASCADE |
| organization_id | bigint unsigned | No | — | FK → organizations.id, CASCADE |
| quantity | decimal(15,3) | No | 0 | |
| cost_price | decimal(15,2) | No | 0 | Giá vốn trung bình |
| timestamps | | | | |
| — | — | — | — | UNIQUE(product_id, variant_id, organization_id) |

### `inventory_transactions`
Thẻ kho — lịch sử giao dịch tồn kho.

| Cột | Kiểu | Nullable | Mặc định | Ràng buộc / Ghi chú |
|-----|------|----------|----------|---------------------|
| id | bigint unsigned | No | — | PK |
| inventory_id | bigint unsigned | No | — | FK → inventory.id, CASCADE |
| type | varchar(255) | No | — | import, export, sale, return, adjust, transfer |
| quantity_change | decimal(15,3) | No | — | +/- số lượng |
| quantity_after | decimal(15,3) | No | — | Tồn sau giao dịch |
| cost_price | decimal(15,2) | No | 0 | Giá vốn tại thời điểm |
| reference_type | varchar(255) | Yes | null | Polymorphic |
| reference_id | bigint unsigned | Yes | null | Polymorphic |
| note | text | Yes | null | |
| created_by | bigint unsigned | Yes | null | FK → users.id |
| created_at | timestamp | Yes | null | |

**Quan hệ:**
- `products` n-1 với `product_categories`, `brands`, `product_units`.
- `products` n-n với `locations` (qua `product_location`), `product_units` (qua `product_product_unit`).
- `products` 1-n với `product_variants`, `product_components`, `inventory`.
- `products` 1-n (polymorphic) với `media` qua `collection_name = product-images`.
- `inventory` 1-n với `inventory_transactions`.


---

## 7. Thiết lập giá (Price Lists)

### `price_lists`
Bảng giá: quản lý nhiều bảng giá (sỉ, VIP, chi nhánh, khuyến mãi).

| Cột | Kiểu | Nullable | Mặc định | Ghi chú |
|-----|------|----------|----------|---------|
| id | bigint unsigned | No | — | PK |
| name | varchar(255) | No | — | Tên bảng giá |
| slug | varchar(255) | No | — | UNIQUE |
| description | text | Yes | null | |
| status | varchar | No | active | active, inactive |
| is_default | boolean | No | false | Bảng giá chung |
| start_date | datetime | Yes | null | Hiệu lực từ |
| end_date | datetime | Yes | null | Hiệu lực đến |
| base_price_list_id | bigint | Yes | null | FK → price_lists, SET NULL |
| formula_type | varchar | Yes | null | percentage, fixed_amount |
| formula_value | decimal(15,2) | Yes | null | VD: -10 = giảm 10% |
| auto_update_from_base | boolean | No | false | |
| add_products_from_base | boolean | No | false | |
| rounding_type | varchar | Yes | null | none/unit/ten/hundred/thousand/ten_thousand |
| rounding_method | varchar | Yes | null | round/ceil/floor |
| cashier_policy | varchar | No | allow_all | allow_all/allow_with_warning/only_in_list |
| created_by | bigint | Yes | null | FK → users |
| updated_by | bigint | Yes | null | FK → users |
| timestamps | | | | |

### `price_list_items`
Chi tiết giá từng sản phẩm/biến thể trong bảng giá.

| Cột | Kiểu | Nullable | Mặc định | Ghi chú |
|-----|------|----------|----------|---------|
| id | bigint unsigned | No | — | PK |
| price_list_id | bigint | No | — | FK → price_lists, CASCADE |
| product_id | bigint | No | — | FK → products, CASCADE |
| variant_id | bigint | Yes | null | FK → product_variants, CASCADE |
| unit_id | bigint | Yes | null | FK → product_units, SET NULL |
| price | decimal(15,2) | No | 0 | Giá bán |
| item_formula_type | varchar | Yes | null | override công thức bảng giá |
| item_formula_value | decimal(15,2) | Yes | null | |
| timestamps | | | | |
| UNIQUE | (price_list_id, product_id, variant_id, unit_id) | | | |

### `price_list_organizations`
Phạm vi áp dụng bảng giá theo chi nhánh (pivot).

| Cột | Kiểu | Nullable | Ghi chú |
|-----|------|----------|---------|
| price_list_id | bigint | No | FK → price_lists, CASCADE |
| organization_id | bigint | No | FK → organizations, CASCADE |
| PK | (price_list_id, organization_id) | | |

---

*File được cập nhật theo migration trong `database/migrations/`.*

