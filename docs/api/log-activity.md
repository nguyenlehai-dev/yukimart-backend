# API Nhật ký truy cập (LogActivity) – Core

Quản lý nhật ký truy cập: thống kê, danh sách, chi tiết, xuất Excel, xóa, xóa hàng loạt, xóa theo khoảng thời gian, xóa toàn bộ.

**Base path:** `/api/log-activities`

---

## Thống kê

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/log-activities/stats` |
| **Query** | `search`, `from_date`, `to_date`, `method_type`, `status_code`, `sort_by`, `sort_order`, `limit`. |
| **Response** | `{ "total": 100 }` |

---

## Danh sách nhật ký

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/log-activities` |
| **Query** | `search` (description, route, ip_address, country, user_type), `from_date`, `to_date`, `method_type`, `status_code`, `sort_by`, `sort_order`, `limit`. |
| **Response** | Paginated collection (LogActivityResource). |

---

## Chi tiết nhật ký

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/log-activities/{id}` |
| **UrlParam** | `id` — ID nhật ký. |
| **Response** | Object nhật ký (LogActivityResource). |

---

## Xuất nhật ký

| | |
|---|---|
| **Method** | GET |
| **Path** | `/api/log-activities/export` |
| **Query** | `search`, `from_date`, `to_date`, `method_type`, `status_code`, `sort_by`, `sort_order`. |
| **Response** | File Excel `log-activities.xlsx`. |

---

## Xóa nhật ký

| | |
|---|---|
| **Method** | DELETE |
| **Path** | `/api/log-activities/{id}` |
| **Response** | `{ "message": "Đã xóa nhật ký thành công!" }`. |

---

## Xóa hàng loạt

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/log-activities/bulk-delete` |
| **Body** | `ids` (array) — danh sách ID nhật ký. |
| **Response** | `{ "message": "Đã xóa thành công N nhật ký!" }`. |

---

## Xóa theo khoảng thời gian

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/log-activities/delete-by-date` |
| **Body** | `from_date` (required, Y-m-d), `to_date` (required, Y-m-d). |
| **Response** | `{ "message": "Đã xóa thành công N nhật ký trong khoảng thời gian!" }`. |

---

## Xóa toàn bộ

| | |
|---|---|
| **Method** | POST |
| **Path** | `/api/log-activities/clear` |
| **Response** | `{ "message": "Đã xóa toàn bộ N nhật ký!" }`. |
