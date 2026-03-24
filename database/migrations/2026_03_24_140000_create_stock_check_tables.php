<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_checks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, balanced, cancelled
            $table->decimal('total_deviation_amount', 18, 2)->default(0); // Tong gia tri chenh lech
            $table->integer('total_increase')->default(0); // So luong tang
            $table->integer('total_decrease')->default(0); // So luong giam
            $table->text('note')->nullable();
            $table->dateTime('check_date')->nullable();
            $table->dateTime('balanced_at')->nullable(); // Thoi diem can bang kho
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('system_quantity', 15, 2)->default(0); // Ton kho he thong
            $table->decimal('actual_quantity', 15, 2)->default(0); // Thuc te kiem
            $table->decimal('deviation', 15, 2)->default(0); // = actual - system
            $table->decimal('cost_price', 18, 2)->default(0);
            $table->decimal('deviation_amount', 18, 2)->default(0); // = deviation * cost_price
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_check_items');
        Schema::dropIfExists('stock_checks');
    }
};
