<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('completed'); // draft, completed, cancelled
            $table->decimal('total_amount', 18, 2)->default(0); // Tong gia tri hang huy
            $table->text('note')->nullable();
            $table->dateTime('disposal_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_disposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_disposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('cost_price', 18, 2)->default(0); // Gia von
            $table->decimal('amount', 18, 2)->default(0); // = qty * cost_price
            $table->text('reason')->nullable(); // Ly do huy
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_disposal_items');
        Schema::dropIfExists('stock_disposals');
    }
};
