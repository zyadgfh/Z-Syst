<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_order_id')->comment('معرف أمر الشراء');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->decimal('quantity_ordered', 15, 3)->default(0)->comment('الكمية المطلوبة');
            $table->decimal('quantity_received', 15, 3)->default(0)->comment('الكمية المستلمة');
            $table->decimal('unit_cost', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->decimal('discount', 12, 3)->default(0)->comment('الخصم');
            $table->decimal('tax', 12, 3)->default(0)->comment('الضريبة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('purchase_order_id', 'fk_purchase_order_items_po_id')
                  ->references('id')->on('purchase_orders')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_purchase_order_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('purchase_order_id', 'idx_purchase_order_items_po_id');
            $table->index('product_id', 'idx_purchase_order_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};