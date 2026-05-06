<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verify Google reCAPTCHA v3 token nếu secret được cấu hình. Khi không có secret,
 * skip silently (dev/local mode) — không fail luồng login/register.
 */
class RecaptchaService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.recaptcha.secret'));
    }

    /**
     * Trả về true nếu token hợp lệ HOẶC không cấu hình recaptcha. False nếu
     * token sai/score thấp.
     */
    public function verify(?string $token, string $expectedAction = ''): bool
    {
        if (! $this->isConfigured()) {
            return true; // dev mode — skip
        }
        if (empty($token)) {
            return false;
        }

        try {
            $res = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $token,
                ])
                ->json();
        } catch (\Throwable $e) {
            Log::warning('[recaptcha] verify failed: '.$e->getMessage());
            // Network fail → fail-open để không khoá user khi Google chậm.
            return true;
        }

        if (! ($res['success'] ?? false)) {
            return false;
        }
        $minScore = (float) (config('services.recaptcha.min_score') ?? 0.5);
        if (isset($res['score']) && $res['score'] < $minScore) {
            return false;
        }
        if ($expectedAction !== '' && isset($res['action']) && $res['action'] !== $expectedAction) {
            return false;
        }
        return true;
    }
}
