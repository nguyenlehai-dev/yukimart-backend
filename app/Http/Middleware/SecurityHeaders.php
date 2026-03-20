<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware thêm Security Headers vào mọi response.
 *
 * Chống: clickjacking, MIME sniffing, XSS, data leaking.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Chống clickjacking — không cho nhúng trong iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // Chống MIME sniffing — trình duyệt không tự đoán content type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Chống XSS (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Kiểm soát thông tin referrer gửi đi
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Tắt quyền truy cập camera, mic, geo, payment
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS — bắt buộc HTTPS (chỉ khi production)
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Không cache response chứa dữ liệu auth
        if ($request->is('api/auth/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
