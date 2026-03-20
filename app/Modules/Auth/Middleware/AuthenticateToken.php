<?php

namespace App\Modules\Auth\Middleware;

use App\Helpers\ApiResponse;
use App\Modules\Auth\Models\PersonalAccessToken;
use App\Modules\Auth\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware xác thực token
 *
 * Đọc token từ HTTP-only cookie,
 * kiểm tra hợp lệ, gắn user vào request.
 *
 * Flow: Cookie → Tìm token → Kiểm tra hạn → Kiểm tra user → ✅
 */
class AuthenticateToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->cookie(AuthService::TOKEN_COOKIE);

        if (!$plainToken) {
            return ApiResponse::unauthorized('Chưa đăng nhập.', 'TOKEN_MISSING');
        }

        $hashedToken = hash('sha256', $plainToken);
        $accessToken = PersonalAccessToken::with('user')
            ->where('token', $hashedToken)
            ->first();

        if (!$accessToken) {
            return ApiResponse::unauthorized('Phiên đăng nhập không hợp lệ.', 'TOKEN_INVALID');
        }

        if ($accessToken->isExpired()) {
            $accessToken->delete();
            return ApiResponse::unauthorized('Phiên đã hết hạn. Vui lòng đăng nhập lại.', 'TOKEN_EXPIRED');
        }

        if (!$accessToken->user) {
            $accessToken->delete();
            return ApiResponse::unauthorized('Tài khoản không tồn tại.', 'USER_NOT_FOUND');
        }

        $accessToken->updateQuietly(['last_used_at' => now()]);

        $request->setUserResolver(fn () => $accessToken->user);
        $request->attributes->set('current_token', $accessToken);

        return $next($request);
    }
}
