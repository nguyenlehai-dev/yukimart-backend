<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->string('product_type')->nullable();
            $table->string('category_path')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('brand')->nullable()->index();
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->decimal('cost', 14, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('threshold')->default(0);
            $table->string('unit')->nullable();
            $table->string('status')->default('draft')->index(); // active | draft | out_of_stock
            $table->text('description')->nullable();
            $table->text('image_urls')->nullable(); // JSON-encoded list of URLs
            $table->boolean('is_hot_deal')->default(false);
            $table->boolean('is_suggested')->default(false);
            $table->string('warehouse')->nullable();
            $table->decimal('weight', 14, 3)->nullable();
            $table->integer('points')->nullable();
            $table->json('extra')->nullable(); // dump các cột chưa map vào schema chính
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};
