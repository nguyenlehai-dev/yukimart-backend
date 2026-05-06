<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shop_products', 'original_price')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->decimal('original_price', 14, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('shop_products', 'wholesale_price')) {
            Schema::table('shop_products', function (Blueprint $table) {
                $table->decimal('wholesale_price', 14, 2)->default(0);
            });
        }

        DB::table('shop_products')
            ->where('sale_price', '>', 0)
            ->where('original_price', 0)
            ->update(['original_price' => DB::raw('sale_price')]);

        DB::table('shop_products')
            ->where('sale_price', '>', 0)
            ->where('wholesale_price', 0)
            ->update(['wholesale_price' => DB::raw('sale_price')]);
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            if (Schema::hasColumn('shop_products', 'original_price')) {
                $table->dropColumn('original_price');
            }
            if (Schema::hasColumn('shop_products', 'wholesale_price')) {
                $table->dropColumn('wholesale_price');
            }
        });
    }
};
