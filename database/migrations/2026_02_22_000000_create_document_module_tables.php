<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_issuing_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_issuing_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_signers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('so_ky_hieu')->unique();
            $table->string('ten_van_ban');
            $table->longText('noi_dung')->nullable();
            $table->foreignId('issuing_agency_id')->nullable()->constrained('document_issuing_agencies')->nullOnDelete();
            $table->foreignId('issuing_level_id')->nullable()->constrained('document_issuing_levels')->nullOnDelete();
            $table->foreignId('signer_id')->nullable()->constrained('document_signers')->nullOnDelete();
            $table->date('ngay_ban_hanh')->nullable();
            $table->date('ngay_xuat_ban')->nullable();
            $table->date('ngay_hieu_luc')->nullable();
            $table->date('ngay_het_hieu_luc')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('document_document_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'document_type_id']);
        });

        Schema::create('document_document_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_field_id')->constrained('document_fields')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'document_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_document_field');
        Schema::dropIfExists('document_document_type');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_fields');
        Schema::dropIfExists('document_signers');
        Schema::dropIfExists('document_issuing_levels');
        Schema::dropIfExists('document_issuing_agencies');
        Schema::dropIfExists('document_types');
    }
};
