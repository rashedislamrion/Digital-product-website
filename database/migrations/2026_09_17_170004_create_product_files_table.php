<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_version_id')->constrained('product_versions')->cascadeOnDelete();
            $table->string('storage_disk')->default('s3_secure');
            $table->string('storage_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('mime_type');
            $table->char('checksum_sha256', 64);
            $table->boolean('is_scanned_safe')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_files');
    }
};
