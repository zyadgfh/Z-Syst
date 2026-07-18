<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_take_id')->comment('معرف الجرد');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->decimal('system_quantity', 15, 3)->default(0)->comment('الكمية في النظام');
            $table->decimal('counted_quantity', 15, 3)->default(0)->comment('الكمية المحصرة');
            $table->decimal('difference', 15, 3)->default(0)->comment('الفرق');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('stock_take_id', 'fk_stock_take_items_take_id')
                  ->references('id')->on('stock_takes')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_stock_take_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('stock_take_id', 'idx_stock_take_items_take_id');
            $table->index('product_id', 'idx_stock_take_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
    }
};