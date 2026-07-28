<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_images')) {
            return;
        }
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('path', 500)->comment('مسار الصورة');
            $table->string('alt_text', 255)->nullable()->comment('النص البديل');
            $table->integer('sort_order')->default(0)->comment('الترتيب');
            $table->boolean('is_primary')->default(false)->comment('رئيسي');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('product_id', 'fk_product_images_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('product_id', 'idx_product_images_product_id');
            $table->index('is_primary', 'idx_product_images_is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};