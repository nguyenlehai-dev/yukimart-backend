# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Đăng nhập qua <code>POST /api/auth/login</code> với email và password để nhận <code>access_token</code>. Gửi token trong header <code>Authorization: Bearer {token}</code> cho các endpoint cần xác thực.<br><br><strong>X-Organization-Id (bắt buộc cho hầu hết endpoint yêu cầu auth):</strong> Các endpoint trong nhóm users, roles, permissions, organizations, posts, post-categories, documents, log-activities, settings... cần thêm header <code>X-Organization-Id: {organization_id}</code> để xác định tổ chức làm việc. Các route <code>/api/auth/*</code> (login, logout, switch-organization, forgot-password, reset-password) không cần header này.
