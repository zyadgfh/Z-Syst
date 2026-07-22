<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('discount_id')->comment('معرف الخصم');
            $table->uuid('product_id')->nullable()->comment('معرف المنتج (إذا كان الخصم على منتج محدد)');
            $table->uuid('category_id')->nullable()->comment('معرف الفئة (إذا كان الخصم على فئة)');
            $table->uuid('manufacturer_id')->nullable()->comment('معرف المصنع (إذا كان الخصم على مصنع)');

            $table->foreign('discount_id', 'fk_pd_discount_id')->references('id')->on('discounts')->onDelete('cascade');
            $table->foreign('product_id', 'fk_pd_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id', 'fk_pd_category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('manufacturer_id', 'fk_pd_manufacturer_id')->references('id')->on('manufacturers')->onDelete('cascade');

            $table->index('discount_id', 'idx_pd_discount_id');
            $table->index('product_id', 'idx_pd_product_id');
            $table->index('category_id', 'idx_pd_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discounts');
    }
};

