# Bổ sung: Tin tức thuộc nhiều category và lượt xem bài viết

## Tóm tắt

- **Một bài viết có thể thuộc nhiều danh mục:** Quan hệ nhiều-nhiều giữa `Post` và `PostCategory` qua bảng pivot `post_post_category`.
- **Lượt xem:** Cột `view_count` trên bảng `posts`, mặc định 0; API `POST /api/posts/{id}/view` dùng để tăng lượt xem khi người dùng xem bài.

---

## Thay đổi cơ sở dữ liệu

1. **Bảng `post_post_category` (pivot)**  
   - `post_id` → `posts.id` (cascade delete)  
   - `post_category_id` → `post_categories.id` (cascade delete)  
   - Unique (`post_id`, `post_category_id`)

2. **Bảng `posts`**  
   - Thêm cột `view_count` (unsigned integer, mặc định 0).

3. **Dữ liệu cũ**  
   - Migration đã copy `posts.category_id` sang pivot, sau đó xóa cột `category_id` (chỉ dùng quan hệ n-n).

---

## Model

- **Post:**  
  - Chỉ dùng `categories()` (belongsToMany qua `post_post_category`), không còn `category_id` / `category()`.
  - `view_count` (fillable + cast integer).  
  - Lọc theo danh mục: query `category_id` áp dụng `whereHas('categories', ...)`.

- **PostCategory:**  
  - `posts()` đổi từ hasMany sang belongsToMany (qua `post_post_category`).

---

## API

- **Tạo/cập nhật bài viết:**  
  - Chỉ chấp nhận `category_ids` (mảng, tối đa 20). Khi lưu: sync vào bảng pivot.

- **Response bài viết:**  
  - Luôn trả về `view_count` và `categories` (mảng).

- **Tăng lượt xem:**  
  - `POST /api/posts/{id}/view` → tăng `view_count` và trả về `view_count` mới.

---

## Cách dùng

- Gửi `category_ids: [1, 2, 3]` khi tạo/sửa bài để gán nhiều danh mục.  
- Gọi `POST /api/posts/{id}/view` mỗi khi người dùng xem chi tiết bài (tránh gọi trong show nếu không muốn tăng view khi admin xem).  
- Sắp xếp danh sách theo lượt xem: query `sort_by=view_count`, `sort_order=desc`.
