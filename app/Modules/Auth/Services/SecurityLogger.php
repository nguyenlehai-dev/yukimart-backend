<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Log;

/**
 * Ghi log bảo mật — tất cả events xác thực.
 *
 * Log vào channel riêng: storage/logs/security-YYYY-MM-DD.log
 */
class SecurityLogger
{
    private const CHANNEL = 'security';

    public static function loginSuccess(int $userId, string $email, string $ip, string $userAgent): void
    {
        Log::channel(self::CHANNEL)->info('LOGIN_SUCCESS', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip,
            'user_agent' => self::truncate($userAgent),
        ]);
    }

    public static function loginFailed(string $email, string $ip, string $userAgent, string $reason = 'invalid_credentials'): void
    {
        Log::channel(self::CHANNEL)->warning('LOGIN_FAILED', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => self::truncate($userAgent),
            'reason' => $reason,
        ]);
    }

    public static function registerSuccess(int $userId, string $email, string $ip): void
    {
        Log::channel(self::CHANNEL)->info('REGISTER_SUCCESS', [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip,
        ]);
    }

    public static function logout(int $userId, string $ip): void
    {
        Log::channel(self::CHANNEL)->info('LOGOUT', [
            'user_id' => $userId,
            'ip' => $ip,
        ]);
    }

    public static function passwordChanged(int $userId, string $ip): void
    {
        Log::channel(self::CHANNEL)->info('PASSWORD_CHANGED', [
            'user_id' => $userId,
            'ip' => $ip,
        ]);
    }

    public static function accountLocked(string $email, string $ip, int $minutes): void
    {
        Log::channel(self::CHANNEL)->warning('ACCOUNT_LOCKED', [
            'email' => $email,
            'ip' => $ip,
            'locked_minutes' => $minutes,
        ]);
    }

    public static function suspicious(string $event, string $ip, array $extra = []): void
    {
        Log::channel(self::CHANNEL)->alert('SUSPICIOUS: ' . $event, array_merge([
            'ip' => $ip,
        ], $extra));
    }

    private static function truncate(string $value): string
    {
        return mb_substr($value, 0, 200);
    }
}
