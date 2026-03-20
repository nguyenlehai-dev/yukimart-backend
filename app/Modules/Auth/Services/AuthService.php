<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Service xác thực — toàn bộ logic nghiệp vụ.
 *
 * Bao gồm:
 * - Đăng nhập/đăng ký/đăng xuất
 * - Account lockout (khóa sau 5 lần sai)
 * - Giới hạn session (max 5)
 * - Đổi password (xóa tất cả token cũ)
 * - Cookie secure theo environment
 */
class AuthService
{
    /** Tên cookie lưu token */
    public const TOKEN_COOKIE = 'yukimart_auth';

    /** Thời hạn token (ngày) */
    public const TOKEN_LIFETIME_DAYS = 30;

    /**
     * Xác thực bằng email + mật khẩu.
     *
     * Flow:
     * 1. Tìm user → kiểm tra lockout → verify password
     * 2. Sai → tăng counter → khóa nếu đạt max
     * 3. Đúng → reset counter → tạo token → cookie
     *
     * @return array{user: User, cookie: Cookie}|array{error: string, locked_minutes?: int}
     */
    public function authenticate(string $email, string $password, string $ip, string $userAgent): array
    {
        $user = User::where('email', $email)->first();

        // Email không tồn tại
        if (!$user) {
            SecurityLogger::loginFailed($email, $ip, $userAgent, 'user_not_found');
            return ['error' => 'invalid_credentials'];
        }

        // Tài khoản đang bị khóa
        if ($user->isLocked()) {
            SecurityLogger::loginFailed($email, $ip, $userAgent, 'account_locked');
            return [
                'error' => 'account_locked',
                'locked_minutes' => $user->lockoutMinutesRemaining(),
            ];
        }

        // Sai mật khẩu
        if (!Hash::check($password, $user->password)) {
            $user->incrementFailedAttempts();
            SecurityLogger::loginFailed($email, $ip, $userAgent, 'wrong_password');

            // Vừa bị khóa sau lần thử này?
            if ($user->isLocked()) {
                return [
                    'error' => 'account_locked',
                    'locked_minutes' => $user->lockoutMinutesRemaining(),
                ];
            }

            $remaining = User::MAX_FAILED_ATTEMPTS - $user->failed_login_attempts;
            return ['error' => 'invalid_credentials', 'attempts_remaining' => $remaining];
        }

        // Đăng nhập thành công
        if (Hash::needsRehash($user->password)) {
            $user->update(['password' => Hash::make($password)]);
        }

        $user->resetFailedAttempts();
        $this->enforceSessionLimit($user);

        $plainToken = $this->createToken($user, $ip, $userAgent);
        $cookie = $this->makeAuthCookie($plainToken);

        SecurityLogger::loginSuccess($user->id, $email, $ip, $userAgent);

        return [
            'user' => $user,
            'cookie' => $cookie,
        ];
    }

    /**
     * Đăng ký tài khoản mới + auto-login.
     *
     * @return array{user: User, cookie: Cookie}
     */
    public function register(array $data, string $ip, string $userAgent): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'retail',
        ]);

        $plainToken = $this->createToken($user, $ip, $userAgent);
        $cookie = $this->makeAuthCookie($plainToken);

        SecurityLogger::registerSuccess($user->id, $user->email, $ip);

        return [
            'user' => $user,
            'cookie' => $cookie,
        ];
    }

    /**
     * Đăng xuất — xóa token + xóa cookie.
     */
    public function logout(string $bearerToken, string $ip): Cookie
    {
        $hashed = hash('sha256', $bearerToken);
        $token = PersonalAccessToken::where('token', $hashed)->first();

        if ($token) {
            SecurityLogger::logout($token->user_id, $ip);
            $token->delete();
        }

        return $this->forgetAuthCookie();
    }

    /**
     * Đổi mật khẩu — xóa TẤT CẢ token cũ → tạo token mới.
     * Hiệu quả: kick ra khỏi mọi thiết bị khác.
     *
     * @return array{cookie: Cookie}|null
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword, string $ip, string $userAgent): ?array
    {
        // Verify mật khẩu hiện tại
        if (!Hash::check($currentPassword, $user->password)) {
            SecurityLogger::suspicious('WRONG_CURRENT_PASSWORD', $ip, [
                'user_id' => $user->id,
            ]);
            return null;
        }

        // Cập nhật password
        $user->update(['password' => Hash::make($newPassword)]);

        // Xóa TẤT CẢ token cũ → kick mọi session
        $user->tokens()->delete();

        // Tạo token mới cho session hiện tại
        $plainToken = $this->createToken($user, $ip, $userAgent);
        $cookie = $this->makeAuthCookie($plainToken);

        SecurityLogger::passwordChanged($user->id, $ip);

        return ['cookie' => $cookie];
    }

    /**
     * Xóa tất cả token (đăng xuất mọi thiết bị).
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Đếm session đang hoạt động.
     */
    public function getActiveSessionCount(User $user): int
    {
        return $user->tokens()->valid()->count();
    }

    // ──────────────── Private ────────────────

    /**
     * Giới hạn max session. Nếu vượt → xóa token cũ nhất.
     */
    private function enforceSessionLimit(User $user): void
    {
        $activeCount = $user->tokens()->valid()->count();

        if ($activeCount >= User::MAX_ACTIVE_SESSIONS) {
            // Xóa token cũ nhất cho đến khi còn (max - 1)
            $tokensToDelete = $activeCount - User::MAX_ACTIVE_SESSIONS + 1;

            $user->tokens()
                ->valid()
                ->orderBy('last_used_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->limit($tokensToDelete)
                ->delete();
        }
    }

    private function createToken(User $user, string $ip, string $userAgent): string
    {
        $plainToken = Str::random(64);

        $user->tokens()->create([
            'name' => 'api',
            'token' => hash('sha256', $plainToken),
            'ip_address' => $ip,           // tự encrypt qua Model accessor
            'user_agent' => mb_substr($userAgent, 0, 500), // tự encrypt qua Model accessor
            'expires_at' => now()->addDays(self::TOKEN_LIFETIME_DAYS),
        ]);

        return $plainToken;
    }

    /**
     * Tạo HTTP-only cookie.
     * secure=true khi production (HTTPS).
     */
    private function makeAuthCookie(string $plainToken): Cookie
    {
        return cookie(
            name: self::TOKEN_COOKIE,
            value: $plainToken,
            minutes: self::TOKEN_LIFETIME_DAYS * 24 * 60,
            path: '/',
            secure: app()->environment('production'),
            httpOnly: true,
            sameSite: 'Lax',
        );
    }

    private function forgetAuthCookie(): Cookie
    {
        return cookie()->forget(self::TOKEN_COOKIE);
    }

    /**
     * Dọn token hết hạn (chạy bằng schedule).
     */
    public static function cleanupExpiredTokens(): int
    {
        return PersonalAccessToken::where('expires_at', '<', now())->delete();
    }
}
