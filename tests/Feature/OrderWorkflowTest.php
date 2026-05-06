<?php

namespace Tests\Feature;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use App\Modules\ShopProduct\Models\ShopEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke tests cho ShopEntryObserver state machine + entity validation rules.
 */
class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Cần PostgreSQL cho jsonb columns');
        }
    }

    private function adminActing(): User
    {
        $admin = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->assignRole($role);
        Sanctum::actingAs($admin);
        return $admin;
    }

    public function test_invalid_status_transition_rejected(): void
    {
        $this->adminActing();

        $entry = ShopEntry::create([
            'entity' => 'orders',
            'data' => ['status' => 'completed', 'total' => 100000],
            'search_text' => '',
        ]);

        // completed → pending là transition không hợp lệ.
        $res = $this->putJson("/api/shop/orders/{$entry->id}", ['status' => 'pending']);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['status']);

        // Vẫn còn completed (không bị flip).
        $this->assertEquals('completed', $entry->fresh()->data['status']);
    }

    public function test_valid_status_transition_pending_to_processing(): void
    {
        $this->adminActing();
        $entry = ShopEntry::create([
            'entity' => 'orders',
            'data' => ['status' => 'pending', 'total' => 100000],
            'search_text' => '',
        ]);

        $res = $this->putJson("/api/shop/orders/{$entry->id}", ['status' => 'processing']);
        $res->assertStatus(200);
        $this->assertEquals('processing', $entry->fresh()->data['status']);
    }

    public function test_entity_validation_rejects_invalid_invoice_status(): void
    {
        $this->adminActing();

        $res = $this->postJson('/api/shop/invoices', [
            'code' => 'HD-X',
            'status' => 'foobar', // không thuộc enum unpaid|paid|partial
            'total' => 100000,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['status']);
    }

    public function test_entity_validation_requires_customer_name_on_create(): void
    {
        $this->adminActing();

        $res = $this->postJson('/api/shop/customers', [
            'email' => 'no-name@example.com',
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }
}
