<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_variants')) {
            return;
        }
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('variant_name', 255)->comment('اسم المتغير');
            $table->string('barcode', 100)->comment('الباركود');
            $table->integer('pack_size')->default(1)->comment('حجم العلبة');
            $table->string('pack_unit', 50)->nullable()->comment('وحدة التعبئة');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->decimal('selling_price', 12, 3)->default(0)->comment('سعر البيع');
            $table->string('sku', 100)->nullable()->comment('رمز المنتج');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('product_id', 'fk_product_variants_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['product_id', 'barcode'], 'uniq_product_variants_barcode');

            // Indexes
            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('is_active', 'idx_product_variants_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};