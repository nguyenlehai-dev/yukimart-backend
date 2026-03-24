# Phân tích tổng quan cấu trúc dự án và tư vấn gói thejano/laravel-domain-driven-design

**Ngày tạo:** 2025-02-16  
**Mục đích:** Phân tích cấu trúc hiện tại, đánh giá khả năng mở rộng và tư vấn có nên dùng gói `thejano/laravel-domain-driven-design` để quản lý chức năng theo module.

---

## 1. Tổng quan cấu trúc dự án

### 1.1 Stack & công cụ

- **Framework:** Laravel 12, PHP 8.2
- **Auth:** Laravel Sanctum
- **Tài liệu API:** Knuckleswtf/scribe
- **Excel:** Maatwebsite/Excel (export, import)

### 1.2 Cấu trúc thư mục chính

Dự án đang dùng mô hình **module theo tính năng** (feature/module), không dùng DDD package:

```
app/
├── Exports/              # Export Excel (PostsExport, UsersExport) – nằm ngoài module
├── Http/
│   ├── Controllers/Controller.php
│   └── Requests/FilterRequest.php   # Request dùng chung cho filter
├── Imports/              # Import Excel – nằm ngoài module
├── Models/               # Eloquent models – nằm ngoài module (User, Post)
├── Modules/
│   ├── Auth/             # Module xác thực
│   │   ├── AuthController.php
│   │   └── Routes/auth.php
│   ├── Post/             # Module bài viết
│   │   ├── PostController.php
│   │   ├── Routes/post.php
│   │   ├── Requests/     # Store, Update, BulkDestroy, BulkUpdateStatus, Import, ChangeStatus
│   │   └── Resources/    # PostResource, PostCollection
│   └── User/             # Module người dùng
│       ├── UserController.php
│       ├── Routes/user.php
│       ├── Requests/
│       └── Resources/
└── Providers/
```

### 1.3 Cách hoạt động

- **Routes:** Đăng ký thủ công trong `routes/api.php`: từng module được `require` theo prefix (`auth`, `users`, `posts`).
- **Controller:** Mỗi module có một controller chính (PostController, UserController), gọi trực tiếp `Model::...` và dùng Request/Resource của module.
- **Models:** Đặt chung trong `app/Models`, có scope `filter()`, quan hệ và logic boot (vd: `created_by`/`updated_by`).
- **Request dùng chung:** `App\Modules\Core\Requests\FilterRequest` dùng cho index (search, sort, limit) của User và Post.

---

## 2. Điểm mạnh của cấu trúc hiện tại

| Khía cạnh | Đánh giá |
|-----------|----------|
| **Tách theo tính năng** | Rõ ràng: Auth, User, Post tách biệt; dễ tìm code theo nghiệp vụ. |
| **Nhất quán** | User và Post có cùng pattern: Controller, Routes, Requests, Resources. |
| **Đơn giản** | Không phụ thuộc package ngoài cho cấu trúc; dễ onboard. |
| **Mở rộng module mới** | Thêm thư mục `Modules/TênModule` + một dòng `require` trong `api.php`. |

---

## 3. Điểm cần cải thiện (không bắt buộc dùng DDD)

- **Models/Exports/Imports nằm ngoài module:** Khi nhiều module hơn, có thể cân nhắc đưa Model (và Export/Import nếu gắn chặt) vào từng module để module tự chứa đủ.
- **Đăng ký route thủ công:** Mỗi module mới phải sửa `api.php`. Có thể sau này dùng Service Provider per module để auto-load routes (vẫn không cần DDD package).
- **Logic nghiệp vụ trong Controller:** Hiện tại Controller gọi trực tiếp `Model::create()`, `Model::update()`, v.v. Khi nghiệp vụ phức tạp hơn (nhiều bước, event, rule), nên tách sang **Service** hoặc **Action** trong chính cấu trúc module hiện tại.

---

## 4. Gói thejano/laravel-domain-driven-design

### 4.1 Chức năng chính

- Tạo **Domain** (tương đương “module” nhưng theo DDD) bằng lệnh: `php artisan d:create Album`.
- Scaffold đặt trong `app/Domain/{Tên}/`, ví dụ:
  - **Models, Http** (Controllers, Requests, Resources, Middleware)
  - **Actions, Services** – tách logic nghiệp vụ khỏi controller
  - **Events, Listeners, Jobs, Observers**
  - **Policies, Exceptions, Data** (objects)

### 4.2 So sánh nhanh với cấu trúc hiện tại

| Tiêu chí | Cấu trúc hiện tại (Modules) | thejano/laravel-domain-driven-design (Domain) |
|----------|-----------------------------|-----------------------------------------------|
| Vị trí code | `app/Modules/Post/` | `app/Domain/Post/` (hoặc tên domain) |
| Tạo module mới | Tạo thư mục + file thủ công hoặc copy module cũ | `php artisan d:create TênDomain` |
| Services / Actions | Chưa có (logic trong Controller) | Có sẵn thư mục Actions, Services |
| Events / Listeners | Chưa tách theo module | Có trong từng domain |
| Models | `app/Models/` chung | Trong từng domain (Models/) |
| Độ phức tạp | Đơn giản, dễ hiểu | DDD đầy đủ hơn, nhiều lớp hơn |

---

## 5. Tư vấn: có nên cài thejano/laravel-domain-driven-design?

### 5.1 Khuyến nghị: **Chưa nên** cài package này ở giai đoạn hiện tại

Lý do chính:

1. **Dự án đã có modular rõ ràng**  
   Cấu trúc `Modules/` với Controller, Routes, Requests, Resources đã đủ cho quy mô hiện tại (Auth, User, Post) và cho thêm vài module tương tự. Chưa có nhu cầu bắt buộc phải đổi sang “Domain” và DDD scaffold.

2. **Tránh hai mô hình cùng lúc**  
   Nếu cài package và tạo domain mới (vd: `app/Domain/Album`) trong khi vẫn giữ `app/Modules/Post`, sẽ tồn tại hai cách tổ chức (Modules vs Domain). Điều này dễ gây rối và tranh cãi “code mới đặt ở đâu”. Chuyển hết sang Domain nghĩa là refactor lớn (Models, Routes, namespace, Exports/Imports…) mà với bài toán hiện tại chưa thấy lợi ích tương xứng.

3. **Chi phí refactor cao, lợi ích chưa rõ**  
   Package phù hợp khi bạn **muốn đi theo DDD** (Services, Actions, Events, Bounded Context) ngay từ đầu hoặc khi nghiệp vụ đã phức tạp. Với CRUD + filter + bulk + import/export như hiện tại, có thể cải thiện dần bằng cách **tự thêm** Services/Actions trong chính `Modules/` mà không cần đổi cả kiến trúc.

4. **Mở rộng có thể làm trong cấu trúc hiện tại**  
   - Thêm module: tạo `Modules/TênModule` (và có thể chuẩn hóa bằng script/stub riêng).  
   - Tách logic nghiệp vụ: tạo `Modules/Post/Services/PostService.php` hoặc `Modules/Post/Actions/CreatePostAction.php` và gọi từ Controller.  
   - Models có thể giữ trong `app/Models` hoặc từ từ chuyển vào từng module nếu muốn module “đóng gói” hơn.

### 5.2 Khi nào nên cân nhắc lại (có thể dùng DDD hoặc package tương tự)

- Dự án mở rộng nhiều **bounded context** (nhiều domain nghiệp vụ khác biệt) và cần tách rõ từng context.
- Nghiệp vụ phức tạp: nhiều bước, event, rule, tích hợp ngoài; team muốn chuẩn DDD (Services, Actions, Events, Data objects).
- Team quyết định **refactor toàn bộ** sang DDD và chấp nhận đổi namespace, cấu trúc thư mục; khi đó công cụ scaffold như thejano/ddd có thể hữu ích.

---

## 6. Hướng cải thiện gợi ý (không dùng package)

- **Giữ nguyên** `app/Modules/` và quy ước hiện tại.
- **Thêm lớp Service (hoặc Action)** trong từng module khi một use case bắt đầu phức tạp (vd: `Modules/Post/Services/PostService.php`), Controller chỉ gọi service.
- **Tùy chọn:** Đưa Model (và nếu cần, Export/Import) vào trong từng module để module tự chứa đủ (ví dụ `Modules/Post/Models/Post.php` và cập nhật namespace/autoload).
- **Tùy chọn:** Module Service Provider đăng ký route cho từng module (scan hoặc require file trong `Modules/*/Routes/`) để tránh sửa tay `api.php` mỗi lần thêm module.

---

## 7. Kết luận

- **Cấu trúc hiện tại:** Rõ ràng, modular, dễ mở rộng thêm module và phù hợp quy mô hiện tại.
- **thejano/laravel-domain-driven-design:** Hữu ích khi bạn chủ đích làm DDD và sẵn sàng refactor; với tình trạng dự án hiện tại, **chưa nên** cài để tránh hai mô hình song song và refactor không cần thiết.
- **Khuyến nghị:** Tiếp tục phát triển trên cấu trúc `Modules/`, bổ sung Service/Action và (nếu cần) đưa Model vào module khi có nhu cầu rõ ràng; chỉ cân nhắc DDD package khi dự án và yêu cầu nghiệp vụ thực sự cần kiến trúc DDD đầy đủ.
