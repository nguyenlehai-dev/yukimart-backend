<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng giá
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('is_default')->default(false); // Bảng giá chung

            // Hiệu lực
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // Công thức tính giá từ bảng giá gốc
            $table->foreignId('base_price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->string('formula_type')->nullable(); // percentage, fixed_amount, null = manual
            $table->decimal('formula_value', 15, 2)->nullable(); // VD: -10 = giảm 10%, +5000 = tăng 5000đ
            $table->boolean('auto_update_from_base')->default(false); // Tự động cập nhật theo bảng giá gốc
            $table->boolean('add_products_from_base')->default(false); // Thêm HH từ bảng giá gốc

            // Làm tròn
            $table->string('rounding_type')->nullable(); // none, unit, ten, hundred, thousand, ten_thousand
            $table->string('rounding_method')->nullable(); // round, ceil, floor

            // Kiểm soát thu ngân
            $table->string('cashier_policy')->default('allow_all'); // allow_all, allow_with_warning, only_in_list

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Chi tiết giá từng sản phẩm trong bảng giá
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            $table->decimal('price', 15, 2)->default(0); // Giá bán trong bảng giá này

            // Công thức riêng cho item (override công thức bảng giá)
            $table->string('item_formula_type')->nullable();
            $table->decimal('item_formula_value', 15, 2)->nullable();

            $table->timestamps();

            $table->unique(['price_list_id', 'product_id', 'variant_id', 'unit_id'], 'price_list_item_unique');
        });

        // Phạm vi áp dụng: chi nhánh
        Schema::create('price_list_organizations', function (Blueprint $table) {
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->primary(['price_list_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_organizations');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
    }
};
