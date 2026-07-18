<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_transfer_id')->comment('معرف التحويل');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->decimal('quantity_requested', 15, 3)->default(0)->comment('الكمية المطلوبة');
            $table->decimal('quantity_approved', 15, 3)->default(0)->comment('الكمية المعتمدة');
            $table->decimal('quantity_shipped', 15, 3)->default(0)->comment('الكمية المشحونة');
            $table->decimal('quantity_received', 15, 3)->default(0)->comment('الكمية المستلمة');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('stock_transfer_id', 'fk_stock_transfer_items_transfer_id')
                  ->references('id')->on('stock_transfers')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_stock_transfer_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('stock_transfer_id', 'idx_stock_transfer_items_transfer_id');
            $table->index('product_id', 'idx_stock_transfer_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};