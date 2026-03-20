<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service xác minh Google reCAPTCHA v2 Checkbox.
 *
 * User check vào "Tôi không phải robot" → FE gửi token → BE verify.
 * Khi APP_ENV=local và không có secret key → bỏ qua (dev mode).
 */
class RecaptchaService
{
    /** URL API xác minh của Google */
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Xác minh token reCAPTCHA v2.
     *
     * @param ?string $token Token từ checkbox callback
     * @return bool true = đã verify, false = chưa hoặc bot
     */
    public static function verify(?string $token): bool
    {
        $secretKey = config('services.recaptcha.secret_key');

        // Dev mode: không có key → bỏ qua
        if (empty($secretKey)) {
            if (app()->environment('local', 'testing')) {
                return true;
            }
            Log::channel('security')->error('RECAPTCHA_SECRET_KEY chưa được cấu hình!');
            return false;
        }

        // Token rỗng hoặc null → chưa check checkbox
        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret' => $secretKey,
                'response' => $token,
            ]);

            $data = $response->json();

            if (!($data['success'] ?? false)) {
                Log::channel('security')->warning('RECAPTCHA_FAILED', [
                    'errors' => $data['error-codes'] ?? [],
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('RECAPTCHA_ERROR', [
                'message' => $e->getMessage(),
            ]);

            // Google API lỗi → cho qua (không block user)
            return true;
        }
    }

    /**
     * Kiểm tra reCAPTCHA có được bật không.
     */
    public static function isEnabled(): bool
    {
        return !empty(config('services.recaptcha.secret_key'));
    }
}
