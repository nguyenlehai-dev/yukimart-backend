<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng pivot: bài viết (post) thuộc nhiều danh mục (post_category).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_post_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_id', 'post_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_post_category');
    }
};
