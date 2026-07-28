<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_return_items')) {
            return;
        }
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_return_id')->comment('معرف المرتجع');
            $table->uuid('sale_item_id')->nullable()->comment('معرف بند البيع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->decimal('quantity', 15, 3)->default(0)->comment('الكمية');
            $table->decimal('unit_price', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->string('condition', 50)->default('sellable')->comment('الحالة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('sale_return_id', 'fk_sale_return_items_return_id')
                  ->references('id')->on('sale_returns')
                  ->onDelete('cascade');
            $table->foreign('sale_item_id', 'fk_sale_return_items_sale_item_id')
                  ->references('id')->on('sale_items')
                  ->onDelete('set null');
            $table->foreign('product_id', 'fk_sale_return_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('sale_return_id', 'idx_sale_return_items_return_id');
            $table->index('product_id', 'idx_sale_return_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};