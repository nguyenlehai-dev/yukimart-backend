<?php

namespace App\Modules\Auth\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller xác thực (mỏng)
 *
 * Nhận request → gọi service → trả response.
 * KHÔNG có logic nghiệp vụ.
 */
#[OA\Tag(name: "Xác thực", description: "Đăng nhập, đăng ký, đăng xuất, đổi mật khẩu")]
class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    // ──────────────────── ĐĂNG NHẬP ────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        // Xác minh reCAPTCHA trước khi xử lý
        if (!RecaptchaService::verify($request->input('recaptcha_token'))) {
            return ApiResponse::error(
                'Xác minh reCAPTCHA thất bại. Vui lòng thử lại.',
                'RECAPTCHA_FAILED',
                422
            );
        }

        $result = $this->authService->authenticate(
            email: $request->validated('email'),
            password: $request->validated('password'),
            ip: $request->ip(),
            userAgent: $request->userAgent() ?? '',
        );

        // Tài khoản bị khóa
        if (isset($result['error']) && $result['error'] === 'account_locked') {
            $minutes = $result['locked_minutes'] ?? 0;
            return ApiResponse::error(
                "Tài khoản bị khóa tạm thời. Thử lại sau {$minutes} phút.",
                'ACCOUNT_LOCKED',
                423 // Locked
            );
        }

        // Sai thông tin
        if (isset($result['error'])) {
            $msg = 'Email hoặc mật khẩu không đúng.';
            if (isset($result['attempts_remaining'])) {
                $msg .= " Còn {$result['attempts_remaining']} lần thử.";
            }
            return ApiResponse::unauthorized($msg, 'INVALID_CREDENTIALS');
        }

        return ApiResponse::success(
            ['user' => $result['user']->toAuthArray()],
            'Đăng nhập thành công.'
        )->withCookie($result['cookie']);
    }

    // ──────────────────── ĐĂNG KÝ ────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        // Xác minh reCAPTCHA
        if (!RecaptchaService::verify($request->input('recaptcha_token'))) {
            return ApiResponse::error(
                'Xác minh reCAPTCHA thất bại. Vui lòng thử lại.',
                'RECAPTCHA_FAILED',
                422
            );
        }

        $result = $this->authService->register(
            data: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent() ?? '',
        );

        return ApiResponse::created(
            ['user' => $result['user']->toAuthArray()],
            'Đăng ký thành công.'
        )->withCookie($result['cookie']);
    }

    // ──────────────────── ĐĂNG XUẤT ────────────────────

    public function logout(Request $request): JsonResponse
    {
        $plainToken = $request->cookie(AuthService::TOKEN_COOKIE);
        $forgetCookie = null;

        if ($plainToken) {
            $forgetCookie = $this->authService->logout($plainToken, $request->ip());
        }

        $response = ApiResponse::success(message: 'Đăng xuất thành công.');

        if ($forgetCookie) {
            $response->withCookie($forgetCookie);
        }

        return $response;
    }

    // ──────────────────── THÔNG TIN USER ────────────────────

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => $request->user()->toAuthArray(),
        ]);
    }

    // ──────────────────── ĐỔI MẬT KHẨU ────────────────────

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $result = $this->authService->changePassword(
            user: $request->user(),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password'),
            ip: $request->ip(),
            userAgent: $request->userAgent() ?? '',
        );

        if (!$result) {
            return ApiResponse::unauthorized(
                'Mật khẩu hiện tại không đúng.',
                'WRONG_CURRENT_PASSWORD'
            );
        }

        $response = ApiResponse::success(message: 'Đổi mật khẩu thành công. Tất cả thiết bị khác đã bị đăng xuất.');

        if (isset($result['cookie'])) {
            $response->withCookie($result['cookie']);
        }

        return $response;
    }

    // ──────────────────── CẬP NHẬT AVATAR ────────────────────

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $file = $request->file('avatar');
        $path = $this->authService->updateAvatar($request->user(), $file);

        return ApiResponse::success([
            'avatar_url' => asset('storage/' . $path),
            'user' => $request->user()->fresh()->toAuthArray(),
        ], 'Cập nhật ảnh đại diện thành công.');
    }

    // ──────────────────── CSRF COOKIE ────────────────────

    /**
     * Set XSRF-TOKEN cookie cho CSRF protection.
     * FE gọi endpoint này → nhận cookie → gửi kèm mỗi POST request.
     */
    public function csrfCookie(): JsonResponse
    {
        return ApiResponse::success(message: 'CSRF cookie đã được thiết lập.');
    }
}
