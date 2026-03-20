<?php

namespace App\Models;

use App\Modules\Auth\Models\PersonalAccessToken;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Số lần sai tối đa trước khi khóa */
    public const MAX_FAILED_ATTEMPTS = 5;

    /** Thời gian khóa (phút) */
    public const LOCKOUT_MINUTES = 15;

    /** Số session tối đa mỗi user */
    public const MAX_ACTIVE_SESSIONS = 5;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'failed_login_attempts',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
        ];
    }

    // ── Quan hệ ──

    public function tokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class);
    }

    // ── Role helpers ──

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWholesale(): bool
    {
        return $this->role === 'wholesale';
    }

    public function isRetail(): bool
    {
        return $this->role === 'retail';
    }

    // ── Account lockout ──

    /**
     * Tài khoản đang bị khóa?
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Số phút còn lại trước khi mở khóa.
     */
    public function lockoutMinutesRemaining(): int
    {
        if (!$this->isLocked()) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->locked_until, false);
    }

    /**
     * Ghi nhận 1 lần login sai.
     * Nếu đạt mức tối đa → khóa tài khoản.
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->update([
                'locked_until' => now()->addMinutes(self::LOCKOUT_MINUTES),
            ]);
        }
    }

    /**
     * Reset counter khi login thành công.
     */
    public function resetFailedAttempts(): void
    {
        if ($this->failed_login_attempts > 0 || $this->locked_until) {
            $this->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        }
    }

    // ── Format response ──

    /**
     * Get the full URL of the avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    /**
     * Nhãn hiển thị cho từng role.
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'admin'     => 'Quản trị viên',
            'wholesale' => 'Khách sỉ',
            'retail'    => 'Khách lẻ',
            default     => 'Khách hàng',
        };
    }

    public function toAuthArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'role_label' => $this->getRoleLabel(),
            'avatar'     => $this->avatar_url,
        ];
    }
}
