<?php

use App\Modules\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Auth module - public routes (đăng nhập, quên mật khẩu, đặt lại mật khẩu)
Route::prefix('auth')->middleware('log.activity')->group(function () {
    require base_path('app/Modules/Auth/Routes/auth.php');
});

// Module ShopProduct (rebuild tối thiểu sau khi reset DB) — products + import Excel.
Route::prefix('shop/products')->group(function () {
    $c = \App\Modules\ShopProduct\Controllers\ShopProductController::class;
    Route::get('/export', [$c, 'export']);
    Route::post('/import-preview', [$c, 'importPreview']);
    Route::post('/import', [$c, 'import']);
    Route::post('/{id}/adjust-stock', [$c, 'adjustStock'])->whereNumber('id');
    Route::get('/', [$c, 'index']);
    Route::post('/', [$c, 'store']);
    Route::get('/{id}', [$c, 'show'])->whereNumber('id');
    Route::put('/{id}', [$c, 'update'])->whereNumber('id');
    Route::patch('/{id}', [$c, 'update'])->whereNumber('id');
    Route::delete('/{id}', [$c, 'destroy'])->whereNumber('id');
});

// Categories/Brands/Inventory derive từ shop_products đã import.
Route::get('/shop/categories', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'categories']);
Route::get('/shop/brands', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'brands']);
Route::get('/shop/brands/public', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'brands']);
Route::get('/shop/inventory/stats', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'inventoryStats']);

// Generic shop entities — 1 engine xử lý mọi entity admin lưu dạng JSON.
// Whitelist các entity dùng generic; các path khác sẽ rơi xuống stub catch-all.
$genericEntities = implode('|', [
    'customers', 'customer-groups',
    'orders', 'invoices', 'sales-returns',
    'suppliers', 'purchase-invoices', 'purchase-orders', 'purchase-returns',
    'news', 'promotions', 'sections',
    'price-lists',
    'shipments', 'shipping-partners',
    'stock-checks', 'stock-disposals', 'stock-transfers', 'stock-internals',
    'inventory-history',
    'reports-sales', 'reports-finance',
]);
Route::prefix('shop')->group(function () use ($genericEntities) {
    $g = \App\Modules\ShopProduct\Controllers\GenericShopController::class;
    Route::get('/{entity}/export', [$g, 'export'])->where('entity', $genericEntities);
    Route::post('/{entity}/import-preview', [$g, 'importPreview'])->where('entity', $genericEntities);
    Route::post('/{entity}/import', [$g, 'import'])->where('entity', $genericEntities);
    Route::get('/{entity}', [$g, 'index'])->where('entity', $genericEntities);
    Route::post('/{entity}', [$g, 'store'])->where('entity', $genericEntities);
    Route::get('/{entity}/{id}', [$g, 'show'])->where('entity', $genericEntities)->whereNumber('id');
    Route::put('/{entity}/{id}', [$g, 'update'])->where('entity', $genericEntities)->whereNumber('id');
    Route::patch('/{entity}/{id}', [$g, 'update'])->where('entity', $genericEntities)->whereNumber('id');
    Route::delete('/{entity}/{id}', [$g, 'destroy'])->where('entity', $genericEntities)->whereNumber('id');
});

// Inventory history (rỗng — không track movement trong phiên bản tối thiểu).
Route::get('/shop/inventory/history', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'inventoryHistory']);

// Stub cho mọi endpoint /shop/* còn lại (vd /shop/sections/reorder, /shop/news/categories).
// Trả empty list cho GET, 501 cho mutation. TODO: gỡ khi module thật được dựng lại.
Route::any('shop/{any}', [\App\Modules\ShopStub\ShopStubController::class, 'handle'])
    ->where('any', '.*');

// Cấu hình công khai - không cần xác thực
Route::get('/settings/public', [\App\Modules\Core\SettingController::class, 'public'])->middleware('log.activity');
Route::get('/organizations/public', [\App\Modules\Core\OrganizationController::class, 'public'])->middleware('log.activity');
Route::get('/organizations/public-options', [\App\Modules\Core\OrganizationController::class, 'publicOptions'])->middleware('log.activity');

// Route yêu cầu đăng nhập (Bearer token) và đặt ngữ cảnh team cho Spatie Permission
Route::middleware(['auth:sanctum', 'set.permissions.team', 'log.activity'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    Route::prefix('users')->group(function () {
        require base_path('app/Modules/Core/Routes/user.php');
    });
    Route::prefix('permissions')->group(function () {
        require base_path('app/Modules/Core/Routes/permission.php');
    });
    Route::prefix('roles')->group(function () {
        require base_path('app/Modules/Core/Routes/role.php');
    });
    Route::prefix('organizations')->group(function () {
        require base_path('app/Modules/Core/Routes/organization.php');
    });
    Route::prefix('log-activities')->group(function () {
        require base_path('app/Modules/Core/Routes/log_activity.php');
    });
    Route::prefix('settings')->group(function () {
        require base_path('app/Modules/Core/Routes/setting.php');
    });
});
