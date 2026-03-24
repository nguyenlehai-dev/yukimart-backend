<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. product_categories — Nhóm hàng (cây phân cấp)
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('product_categories')->nullOnDelete();
        });

        // 2. brands — Thương hiệu
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. locations — Vị trí lưu trữ
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. product_attributes — Thuộc tính (Size, Màu...)
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. product_attribute_values — Giá trị thuộc tính
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. product_units — Đơn vị tính
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        // 7. products — Sản phẩm chính
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('product'); // product, service, combo, manufacturing
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('base_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('weight', 10, 3)->nullable();
            $table->boolean('allow_negative_stock')->default(false);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->string('status')->default('active'); // active, inactive, discontinued
            $table->boolean('is_active')->default(true);
            $table->integer('point')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
            $table->index(['category_id', 'is_active']);
        });

        // 8. product_variants — Phiên bản sản phẩm
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 9. product_variant_attributes — Pivot: variant ↔ attribute_value
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
            $table->unique(['variant_id', 'attribute_value_id'], 'variant_attr_value_unique');
        });

        // 10. product_components — Thành phần (Combo / Sản xuất)
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->timestamps();

            $table->unique(['parent_product_id', 'component_product_id'], 'product_component_unique');
        });

        // 11. product_location — Pivot: product ↔ location
        Schema::create('product_location', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->primary(['product_id', 'location_id']);
        });

        // 12. product_product_unit — Pivot: product ↔ unit (quy đổi ĐVT)
        Schema::create('product_product_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('conversion_value', 10, 3)->default(1);
            $table->decimal('price', 15, 2)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->unique(['product_id', 'unit_id']);
        });

        // 13. inventory — Tồn kho theo chi nhánh + variant
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'organization_id'], 'inventory_unique');
        });

        // 14. inventory_transactions — Thẻ kho
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->cascadeOnDelete();
            $table->string('type'); // import, export, sale, return, adjust, transfer
            $table->decimal('quantity_change', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->nullableMorphs('reference'); // reference_type, reference_id
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('product_product_unit');
        Schema::dropIfExists('product_location');
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('product_categories');
    }
};
