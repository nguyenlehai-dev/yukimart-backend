# API Cấu hình (Setting) – Core

Quản lý cấu hình hệ thống: lấy cấu hình công khai, lấy toàn bộ (cần auth), cập nhật.

**Base path:** `/api/settings`

---

## Lấy cấu hình công khai

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/settings/public` |
| **Auth** | Không cần |
| **Response** | Object nhóm theo group (general, admin_page, org_select_page, social). Chỉ các key có is_public = true. |

**Ví dụ response:**
```json
{
  "success": true,
  "data": {
    "general": {
      "copyright": "© 2026 QuânDH",
      "designed_by": "QuânDH",
      "language": "vi",
      "time_format": "H:i:s d/m/Y",
      "icon": null,
      "logo": null
    },
    "admin_page": {
      "admin_app_name": "QuânDH Core",
      "admin_logo_title": "Hệ thống quản trị"
    },
    "social": {
      "social_facebook": null,
      "social_email": null
    }
  }
}
```

---

## Lấy toàn bộ cấu hình

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/settings` |
| **Auth** | Bearer token, permission: settings.index |
| **Response** | Object nhóm theo group (general, admin_page, org_select_page, social, api, email, sms, zalo, chat, log). |

---

## Lấy một key

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/settings/{key}` |
| **Auth** | Bearer token, permission: settings.show |
| **UrlParam** | key – Key cấu hình (vd: copyright, log_retention_days). |
| **Response** | Object { key, value, group, label, type }. |

---

## Cập nhật cấu hình

| | |
|---|---|
| **Method** | PUT / PATCH |
| **Path** | `/api/settings` |
| **Auth** | Bearer token, permission: settings.update |
| **Body** | Object key-value. Chỉ cập nhật các key tồn tại. Các key nhạy cảm (password, token) không lưu vào log. |
| **Response** | Toàn bộ cấu hình sau khi cập nhật. |

**Ví dụ body:**
```json
{
  "copyright": "© 2026 QuânDH",
  "language": "vi",
  "log_retention_days": 90,
  "admin_app_name": "Hệ thống quản trị"
}
```
