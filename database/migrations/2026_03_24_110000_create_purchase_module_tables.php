<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Nhà cung cấp ──
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_code')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->decimal('debt', 18, 2)->default(0); // Công nợ hiện tại
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Phiếu nhập hàng ──
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed'); // draft, completed, cancelled
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('debt_amount', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('price', 18, 2)->default(0); // Giá nhập
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0); // = qty * price - discount
            $table->timestamps();
        });

        // ── Phiếu trả hàng nhập ──
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete(); // null = trả nhanh
            $table->string('status')->default('completed'); // completed, cancelled
            $table->decimal('total_amount', 18, 2)->default(0); // Tổng tiền hàng trả
            $table->decimal('supplier_paid', 18, 2)->default(0); // Tiền NCC trả lại
            $table->decimal('debt_amount', 18, 2)->default(0); // Công nợ
            $table->text('note')->nullable();
            $table->dateTime('return_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('price', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
