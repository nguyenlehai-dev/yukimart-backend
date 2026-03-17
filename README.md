# YukiMart Backend API

> Laravel 12 + L5-Swagger API Backend

## Yêu cầu hệ thống
- PHP 8.2+ (khuyến nghị 8.3)
- Composer 2.2+
- MySQL 8.0+ hoặc SQLite (default)

## Cài đặt

```bash
# Clone repo
git clone <repo-url> yukimart-backend
cd yukimart-backend

# Cài dependencies
composer install

# Copy .env
cp .env.example .env

# Generate key
php artisan key:generate

# Chạy migration
php artisan migrate

# Generate Swagger docs
php artisan l5-swagger:generate
```

## Chạy development server

```bash
php artisan serve --port=8000
```

## API Documentation (Swagger)
Truy cập Swagger UI tại: `http://localhost:8000/api/documentation`

## API Endpoints

### Authentication
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/auth/login` | Đăng nhập |
| POST | `/api/auth/register` | Đăng ký |
| POST | `/api/auth/logout` | Đăng xuất |

### Products
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/products` | Danh sách sản phẩm |
| GET | `/api/products/{id}` | Chi tiết sản phẩm |
| POST | `/api/products` | Tạo sản phẩm |
| PUT | `/api/products/{id}` | Cập nhật sản phẩm |
| DELETE | `/api/products/{id}` | Xóa sản phẩm |

### Health Check
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/health` | Kiểm tra trạng thái API |

## Cấu trúc thư mục quan trọng
```
app/Http/Controllers/Api/   # API Controllers (Swagger annotations)
app/Models/Schemas/          # Swagger Schema definitions
config/l5-swagger.php        # Swagger configuration
routes/api.php               # API routes
```

## Lưu ý cho team
- Swagger docs tự động regenerate khi truy cập trong dev mode
- File `.env` KHÔNG được commit vào Git
- Sử dụng `.env.example` làm template
