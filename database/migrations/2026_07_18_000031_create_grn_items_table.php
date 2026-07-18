<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grn_id')->comment('معرف إذن الاستلام');
            $table->uuid('purchase_order_item_id')->nullable()->comment('معرف بند أمر الشراء');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->comment('رقم الدفعة');
            $table->date('expiry_date')->comment('تاريخ الانتهاء');
            $table->date('manufacturing_date')->nullable()->comment('تاريخ التصنيع');
            $table->decimal('quantity_received', 15, 3)->default(0)->comment('الكمية المستلمة');
            $table->decimal('unit_cost', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->string('rack_location', 100)->nullable()->comment('موقع الرف');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('grn_id', 'fk_grn_items_grn_id')
                  ->references('id')->on('goods_received_notes')
                  ->onDelete('cascade');
            $table->foreign('purchase_order_item_id', 'fk_grn_items_poi_id')
                  ->references('id')->on('purchase_order_items')
                  ->onDelete('set null');
            $table->foreign('product_id', 'fk_grn_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('grn_id', 'idx_grn_items_grn_id');
            $table->index('product_id', 'idx_grn_items_product_id');
            $table->index('batch_number', 'idx_grn_items_batch_number');
            $table->index('expiry_date', 'idx_grn_items_expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
    }
};