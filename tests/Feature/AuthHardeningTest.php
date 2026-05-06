<?php

namespace Tests\Feature;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Smoke tests cho hardening auth: rate limit + password rule.
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Cần PostgreSQL cho jsonb columns');
        }
        RateLimiter::clear('login');
    }

    public function test_register_rejects_weak_password(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name' => 'Test', 'email' => 'weak@example.com',
            'password' => 'short', 'password_confirmation' => 'short',
        ]);
        $res->assertStatus(422);
    }

    public function test_register_rejects_no_digit_password(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name' => 'Test', 'email' => 'nodigit@example.com',
            'password' => 'OnlyLetters', 'password_confirmation' => 'OnlyLetters',
        ]);
        $res->assertStatus(422);
    }

    public function test_register_accepts_strong_password(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name' => 'Test', 'email' => 'strong@example.com',
            'password' => 'Strong123', 'password_confirmation' => 'Strong123',
        ]);
        $res->assertStatus(201);
    }

    public function test_login_rate_limit_blocks_after_5_failures(): void
    {
        User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('Right123'),
            'status' => UserStatusEnum::Active->value,
        ]);

        // 5 lần thất bại đầu trả 401, lần 6 phải 429.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'victim@example.com',
                'password' => 'Wrong'.$i,
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'victim@example.com',
            'password' => 'Wrong-final',
        ])->assertStatus(429);
    }

    public function test_inactive_user_blocked_by_middleware(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('Strong123'),
            'status' => UserStatusEnum::Active->value,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        // Tắt user — middleware user.active phải reject với 401.
        $user->status = UserStatusEnum::Inactive->value;
        $user->save();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertStatus(401)
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }
}
