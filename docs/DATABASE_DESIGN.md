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

*File được cập nhật theo migration trong `database/migrations/`.*
