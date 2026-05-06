<?php

namespace Tests\Feature;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke tests cho admin toggle customer active.
 * - Tắt customer → users.status = inactive + revoke tokens
 * - Skip Super Admin để tránh lock-out
 */
class AccountAdminToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Cần PostgreSQL cho jsonb columns');
        }
    }

    public function test_toggle_customer_off_locks_user_and_revokes_tokens(): void
    {
        $admin = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $customer = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        $customer->createToken('test')->plainTextToken;
        $entry = ShopEntry::create([
            'entity' => 'customers',
            'data' => ['user_id' => $customer->id, 'name' => 'C1', 'active' => true],
            'search_text' => 'C1',
        ]);

        $this->assertEquals(1, $customer->tokens()->count());

        $res = $this->postJson("/api/admin/customers/{$entry->id}/active", ['active' => false]);
        $res->assertStatus(200);

        $this->assertEquals(UserStatusEnum::Inactive->value, $customer->fresh()->status);
        $this->assertEquals(0, $customer->tokens()->count(), 'Tokens phải bị revoke');
    }

    public function test_toggle_super_admin_does_not_lock_self(): void
    {
        $admin = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin);

        $entry = ShopEntry::create([
            'entity' => 'customers',
            'data' => ['user_id' => $admin->id, 'name' => 'Admin', 'active' => true],
            'search_text' => 'Admin',
        ]);

        $res = $this->postJson("/api/admin/customers/{$entry->id}/active", ['active' => false]);
        $res->assertStatus(200);

        // Customer entry data->active = false nhưng user.status vẫn active.
        $this->assertEquals(UserStatusEnum::Active->value, $admin->fresh()->status);
    }
}
