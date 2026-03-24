<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nhóm nhà cung cấp
        Schema::create('supplier_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Bổ sung cột cho suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('code')->constrained('supplier_groups')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('group_id')->constrained()->nullOnDelete();
            $table->string('company')->nullable()->after('name');
            $table->string('fax')->nullable()->after('email');
            $table->string('website')->nullable()->after('fax');
        });

        // Giao dịch công nợ NCC (thanh toán, chiết khấu, điều chỉnh)
        Schema::create('supplier_debt_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // payment, discount, adjustment
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('debt_before', 18, 2)->default(0);
            $table->decimal('debt_after', 18, 2)->default(0);
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable(); // cash, bank_transfer, etc.
            $table->text('note')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debt_transactions');
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['company', 'fax', 'website']);
        });
        Schema::dropIfExists('supplier_groups');
    }
};
