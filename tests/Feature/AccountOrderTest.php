<?php

namespace Tests\Feature;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Smoke tests cho luồng đặt đơn (POST /api/account/orders) + observer.
 *
 * Yêu cầu DB testing có hỗ trợ JSONB (PostgreSQL). Nếu chạy SQLite, các test sẽ
 * skip (jsonb migration không tương thích).
 *
 * Chạy: php artisan test --filter=AccountOrderTest
 */
class AccountOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Cần PostgreSQL cho jsonb columns');
        }
    }

    public function test_create_order_requires_auth(): void
    {
        $res = $this->postJson('/api/account/orders', []);
        $res->assertStatus(401);
    }

    public function test_create_order_decrements_stock_and_creates_entry(): void
    {
        $user = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        Sanctum::actingAs($user);

        $product = ShopProduct::create([
            'sku' => 'TEST-1', 'name' => 'Test Product', 'slug' => 'test-product',
            'product_type' => 'simple', 'sale_price' => 100000, 'cost' => 60000,
            'stock' => 10, 'status' => 'active',
        ]);

        $res = $this->postJson('/api/account/orders', [
            'items' => [[
                'id' => $product->id, 'name' => $product->name,
                'quantity' => 3, 'price' => 100000,
            ]],
            'subtotal' => 300000,
            'total' => 300000,
            'payment_method' => 'cod',
            'customer' => [
                'firstName' => 'Test', 'lastName' => 'User',
                'phone' => '0900000000', 'email' => 'test@example.com',
                'address' => '123 Đường ABC', 'city' => 'TP HCM',
            ],
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('shop_entries', ['entity' => 'orders']);
        $this->assertEquals(7, $product->fresh()->stock, 'Stock phải bị trừ từ 10 → 7');
    }

    public function test_create_order_rejects_when_stock_insufficient(): void
    {
        $user = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        Sanctum::actingAs($user);

        $product = ShopProduct::create([
            'sku' => 'TEST-2', 'name' => 'Low Stock', 'slug' => 'low-stock',
            'product_type' => 'simple', 'sale_price' => 100000, 'cost' => 60000,
            'stock' => 2, 'status' => 'active',
        ]);

        $beforeOrders = ShopEntry::where('entity', 'orders')->count();

        $res = $this->postJson('/api/account/orders', [
            'items' => [[
                'id' => $product->id, 'name' => $product->name,
                'quantity' => 5, 'price' => 100000,
            ]],
            'subtotal' => 500000, 'total' => 500000,
            'payment_method' => 'cod',
            'customer' => [
                'firstName' => 'Test', 'lastName' => 'User',
                'phone' => '0900000000', 'email' => 'test@example.com',
                'address' => '123 Đường ABC', 'city' => 'TP HCM',
            ],
        ]);

        $res->assertStatus(422);
        $this->assertEquals(2, $product->fresh()->stock, 'Stock không bị thay đổi khi thiếu hàng');
        $this->assertEquals($beforeOrders, ShopEntry::where('entity', 'orders')->count(), 'Không có đơn nào được tạo');
    }

    public function test_observer_restores_stock_when_order_cancelled(): void
    {
        $user = User::factory()->create(['status' => UserStatusEnum::Active->value]);
        $product = ShopProduct::create([
            'sku' => 'TEST-3', 'name' => 'P3', 'slug' => 'p3',
            'product_type' => 'simple', 'sale_price' => 100000, 'cost' => 60000,
            'stock' => 5, 'status' => 'active',
        ]);

        // Tạo đơn ban đầu (status processing) — stock sẽ giảm sau khi sync
        $entry = ShopEntry::create([
            'entity' => 'orders',
            'data' => [
                'user_id' => $user->id, 'status' => 'processing', 'total' => 200000,
                'items_detail' => [[
                    'id' => $product->id, 'name' => $product->name,
                    'quantity' => 2, 'price' => 100000,
                ]],
            ],
            'search_text' => '',
        ]);

        // Manually decrement to mô phỏng tx createOrder đã trừ
        $product->stock = 3;
        $product->save();

        // Đổi status sang cancelled → observer phải restore
        $data = (array) $entry->data;
        $data['status'] = 'cancelled';
        $entry->update(['data' => $data]);

        $this->assertEquals(5, $product->fresh()->stock, 'Stock phải restore về 5 khi đơn cancelled');
    }
}
