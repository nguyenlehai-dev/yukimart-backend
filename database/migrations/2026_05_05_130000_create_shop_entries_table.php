<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng generic lưu mọi entity admin dạng JSON (customers, orders, suppliers, news,
 * promotions, ...) — phục vụ import Excel + list/CRUD đồng nhất qua GenericShopController.
 *
 * Khi cần entity nào có schema riêng (vd shop_products đã có), chỉ cần build module
 * riêng và bỏ entity đó khỏi whitelist generic ở routes/api.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 64)->index();
            $table->jsonb('data');
            $table->text('search_text')->nullable(); // để filter ?q nhanh
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_entries');
    }
};
