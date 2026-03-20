<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Token xác thực API.
 *
 * - Token lưu dạng hash SHA-256 (không khôi phục được)
 * - IP và user_agent được mã hóa (Crypt::encrypt)
 */
class PersonalAccessToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'ip_address',
        'user_agent',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ── Encrypt/Decrypt dữ liệu nhạy cảm ──

    /**
     * Mã hóa IP trước khi lưu vào DB.
     */
    public function setIpAddressAttribute(?string $value): void
    {
        $this->attributes['ip_address'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Giải mã IP khi đọc từ DB.
     */
    public function getIpAddressAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            // Dữ liệu cũ chưa encrypt → trả nguyên
            return $value;
        }
    }

    /**
     * Mã hóa user_agent trước khi lưu.
     */
    public function setUserAgentAttribute(?string $value): void
    {
        $this->attributes['user_agent'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Giải mã user_agent khi đọc.
     */
    public function getUserAgentAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    // ── Quan hệ ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scope ──

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now());
    }

    // ── Helper ──

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public static function findByPlainToken(string $plainToken): ?self
    {
        return static::where('token', hash('sha256', $plainToken))->first();
    }
}
