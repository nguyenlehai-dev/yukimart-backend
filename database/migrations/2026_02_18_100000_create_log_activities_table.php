<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng nhật ký truy cập của người dùng.
     */
    public function up(): void
    {
        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // Mô tả hành động (vd: Created Product #10)
            $table->string('user_type')->default('Guest');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('route');       // URL đầy đủ
            $table->string('method_type'); // GET, POST, PUT...
            $table->integer('status_code'); // 200, 400, 500...
            $table->string('ip_address');
            $table->string('country')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable(); // Dữ liệu người dùng gửi lên
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['organization_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_activities');
    }
};
