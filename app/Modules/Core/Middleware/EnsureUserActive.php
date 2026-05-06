<?php

namespace App\Modules\Core\Middleware;

use App\Modules\Core\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trả 401 nếu user đang đăng nhập có status khác Active. Khi admin tắt khách
 * hàng (active=false), AccountAdminController sẽ revoke token + flip
 * users.status -> middleware này chặn các request kế tiếp, FE 401-interceptor
 * tự động logout.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && property_exists($user, 'status') ? false : true) {
            // status không tồn tại trên model → bỏ qua
        }

        if ($user && isset($user->status) && $user->status !== UserStatusEnum::Active->value) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khoá',
                'code' => 'ACCOUNT_INACTIVE',
            ], 401);
        }

        return $next($request);
    }
}
