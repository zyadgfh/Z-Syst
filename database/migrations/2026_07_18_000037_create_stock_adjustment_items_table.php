<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_adjustment_items')) {
            return;
        }
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_adjustment_id')->comment('معرف التسوية');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->decimal('quantity_before', 15, 3)->default(0)->comment('الكمية قبل');
            $table->decimal('quantity_after', 15, 3)->default(0)->comment('الكمية بعد');
            $table->decimal('difference', 15, 3)->default(0)->comment('الفرق');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('stock_adjustment_id', 'fk_stock_adjustment_items_adjustment_id')
                  ->references('id')->on('stock_adjustments')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_stock_adjustment_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('stock_adjustment_id', 'idx_stock_adjustment_items_adjustment_id');
            $table->index('product_id', 'idx_stock_adjustment_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};