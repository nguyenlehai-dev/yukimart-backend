# LogActivity - Tích hợp stevebauman/location (MaxMind offline)

## Cấu hình

1. **Tạo tài khoản MaxMind** (miễn phí): https://www.maxmind.com/en/geolite2/signup  
2. **Tạo License Key**: Profile → Manage License Keys → Generate new license key  
3. **Thêm vào `.env`**:
   ```
   MAXMIND_LICENSE_KEY=your_license_key_here
   ```
4. **Tải database GeoLite2**:
   ```bash
   sail artisan location:update
   ```
   File sẽ được lưu tại `database/maxmind/GeoLite2-City.mmdb`.

5. **Cập nhật định kỳ** (database MaxMind cập nhật thường xuyên):
   - Có thể thêm cron: `0 0 * * 0 sail artisan location:update` (mỗi Chủ nhật)

## Lưu ý

- **Offline**: MaxMind dùng file `.mmdb` local, không cần kết nối mạng khi tra cứu.
- **IP local** (127.0.0.1, ::1): Không tra được, `country` sẽ null.
- **LOCATION_TESTING**: Mặc định `false` để lấy IP thật từ request.
