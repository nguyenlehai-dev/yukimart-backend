<?php

use App\Modules\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Auth module - public routes (đăng nhập, quên mật khẩu, đặt lại mật khẩu)
Route::prefix('auth')->middleware('log.activity')->group(function () {
    require base_path('app/Modules/Auth/Routes/auth.php');
});

// Module ShopProduct (rebuild tối thiểu sau khi reset DB) — public list/show, admin mutation/import/export.
Route::prefix('shop/products')->group(function () {
    $c = \App\Modules\ShopProduct\Controllers\ShopProductController::class;
    Route::get('/', [$c, 'index']);
    Route::get('/{id}', [$c, 'show'])->whereNumber('id');

    Route::middleware(['auth:sanctum', 'user.active', 'shop.admin', 'log.activity'])->group(function () use ($c) {
        Route::get('/export', [$c, 'export']);
        Route::post('/import-preview', [$c, 'importPreview']);
        Route::post('/import', [$c, 'import']);
        Route::post('/{id}/adjust-stock', [$c, 'adjustStock'])->whereNumber('id');
        Route::post('/', [$c, 'store']);
        Route::put('/{id}', [$c, 'update'])->whereNumber('id');
        Route::patch('/{id}', [$c, 'update'])->whereNumber('id');
        Route::delete('/{id}', [$c, 'destroy'])->whereNumber('id');
    });
});

// Categories/Brands/Inventory derive từ shop_products đã import.
Route::get('/shop/categories', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'categories']);
Route::get('/shop/brands', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'brands']);
Route::get('/shop/brands/public', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'brands']);
Route::get('/shop/inventory/stats', [\App\Modules\ShopProduct\Controllers\ShopFacetController::class, 'inventoryStats']);
Route::middleware(['auth:sanctum', 'user.active', 'shop.admin', 'log.activity'])->group(function () {
    $f = \App\Modules\ShopProduct\Controllers\ShopFacetController::class;
    Route::post('/shop/categories', [$f, 'storeCategory']);
    Route::put('/shop/categories/{id}', [$f, 'updateCategory'])->whereNumber('id');
    Route::patch('/shop/categories/{id}', [$f, 'updateCategory'])->whereNumber('id');
    Route::delete('/shop/categories/{id}', [$f, 'destroyCategory'])->whereNumber('id');
    Route::post('/shop/brands', [$f, 'storeBrand']);
    Route::put('/shop/brands/{id}', [$f, 'updateBrand'])->whereNumber('id');
    Route::patch('/shop/brands/{id}', [$f, 'updateBrand'])->whereNumber('id');
    Route::delete('/shop/brands/{id}', [$f, 'destroyBrand'])->whereNumber('id');
});

// Generic shop entities — 1 engine xử lý mọi entity admin lưu dạng JSON.
// Whitelist các entity dùng generic; các path khác sẽ rơi xuống stub catch-all.
$genericEntities = implode('|', [
    'customers', 'customer-groups',
    'orders', 'invoices', 'sales-returns',
    'suppliers', 'purchase-invoices', 'purchase-orders', 'purchase-returns',
    'news', 'promotions', 'sections',
    'product-comments',
    'price-lists',
    'shipments', 'shipping-partners',
    'stock-checks', 'stock-disposals', 'stock-transfers', 'stock-internals',
    'inventory-history',
    'reports-sales', 'reports-finance', 'finance-expenses',
]);
Route::prefix('shop')->group(function () use ($genericEntities) {
    $g = \App\Modules\ShopProduct\Controllers\GenericShopController::class;
    $pc = \App\Modules\ShopProduct\Controllers\ProductCommentController::class;
    Route::get('/sections', [$g, 'index'])->defaults('entity', 'sections');
    Route::get('/product-comments/public', [$pc, 'index']);
    Route::post('/product-comments/public', [$pc, 'store']);
    Route::post('/product-comments/public/{id}/reply', [$pc, 'reply'])->whereNumber('id');
    Route::get('/{entity}/public', [$g, 'publicIndex'])->where('entity', 'news|promotions');
    Route::get('/{entity}/slug/{slug}', [$g, 'showBySlug'])->where('entity', 'news|promotions');

    Route::middleware(['auth:sanctum', 'user.active', 'shop.admin', 'log.activity'])->group(function () use ($g, $genericEntities) {
        Route::get('/{entity}/export', [$g, 'export'])->where('entity', $genericEntities);
        Route::post('/{entity}/import-preview', [$g, 'importPreview'])->where('entity', $genericEntities);
        Route::post('/{entity}/import', [$g, 'import'])->where('entity', $genericEntities);
        Route::get('/{entity}', [$g, 'index'])->where('entity', $genericEntities);
        Route::post('/{entity}', [$g, 'store'])->where('entity', $genericEntities);
        Route::put('/sections/reorder', [$g, 'reorderSections']);
        Route::put('/sections/{key}', [$g, 'updateSectionByKey']);
        Route::patch('/sections/{key}', [$g, 'updateSectionByKey']);
        Route::delete('/sections/{key}', [$g, 'destroySectionByKey']);
        Route::put('/sections/{key}/sync-products', [$g, 'syncSectionProducts']);
        Route::get('/{entity}/{id}', [$g, 'show'])->where('entity', $genericEntities)->whereNumber('id');
        Route::put('/{entity}/{id}/status', [$g, 'updateStatus'])->where('entity', $genericEntities)->whereNumber('id');
        Route::patch('/{entity}/{id}/status', [$g, 'updateStatus'])->where('entity', $genericEntities)->whereNumber('id');
        Route::put('/{entity}/{id}', [$g, 'update'])->where('entity', $genericEntities)->whereNumber('id');
        Route::patch('/{entity}/{id}', [$g, 'update'])->where('entity', $genericEntities)->whereNumber('id');
        Route::delete('/{entity}/{id}', [$g, 'destroy'])->where('entity', $genericEntities)->whereNumber('id');
    });
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
Route::middleware(['auth:sanctum', 'user.active', 'set.permissions.team', 'log.activity'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    // Tài khoản cá nhân — mọi dữ liệu scope theo Auth::id() của user đang đăng nhập.
    Route::prefix('account')->group(function () {
        $a = \App\Modules\Auth\AccountController::class;
        Route::get('/orders', [$a, 'orders']);
        Route::post('/orders', [$a, 'createOrder']);
        Route::get('/activities', [$a, 'activities']);
        Route::get('/purchase-history', [$a, 'purchaseHistory']);
        Route::get('/invoices', [$a, 'invoices']);
    });

    // Endpoint admin: toggle khoá/mở khách hàng (sync users.status + revoke token).
    Route::middleware(['shop.admin'])->group(function () {
        $aa = \App\Modules\Auth\AccountAdminController::class;
        Route::post('/admin/customers/{id}/active', [$aa, 'setCustomerActive'])->whereNumber('id');
    });

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
