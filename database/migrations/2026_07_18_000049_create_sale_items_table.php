<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id')->comment('معرف البيع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->uuid('prescription_item_id')->nullable()->comment('معرف بند الوصفة');
            $table->string('name_snapshot', 255)->comment('اسم المنتج المحفوظ');
            $table->decimal('quantity', 15, 3)->default(1)->comment('الكمية');
            $table->decimal('unit_price', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->decimal('discount', 12, 3)->default(0)->comment('الخصم');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('نسبة الضريبة');
            $table->decimal('tax_amount', 12, 3)->default(0)->comment('مبلغ الضريبة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->boolean('is_gift')->default(false)->comment('هدية');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('sale_id', 'fk_sale_items_sale_id')
                  ->references('id')->on('sales')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_sale_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('prescription_item_id', 'fk_sale_items_prescription_item_id')
                  ->references('id')->on('prescription_items')
                  ->onDelete('set null');

            // Indexes
            $table->index('sale_id', 'idx_sale_items_sale_id');
            $table->index('product_id', 'idx_sale_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};