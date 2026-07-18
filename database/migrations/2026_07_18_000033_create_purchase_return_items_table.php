<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_return_id')->comment('معرف المرتجع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->decimal('quantity', 15, 3)->default(0)->comment('الكمية');
            $table->decimal('unit_cost', 12, 3)->default(0)->comment('سعر الوحدة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->text('reason')->nullable()->comment('السبب');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('purchase_return_id', 'fk_purchase_return_items_return_id')
                  ->references('id')->on('purchase_returns')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_purchase_return_items_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // Indexes
            $table->index('purchase_return_id', 'idx_purchase_return_items_return_id');
            $table->index('product_id', 'idx_purchase_return_items_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};