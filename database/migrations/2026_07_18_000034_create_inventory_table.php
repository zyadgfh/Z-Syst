<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory')) {
            return;
        }
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->comment('رقم الدفعة');
            $table->date('expiry_date')->comment('تاريخ الانتهاء');
            $table->date('manufacturing_date')->nullable()->comment('تاريخ التصنيع');
            $table->decimal('quantity', 15, 3)->default(0)->comment('الكمية');
            $table->decimal('reserved_quantity', 15, 3)->default(0)->comment('الكمية المحجوزة');
            $table->decimal('available_quantity', 15, 3)->storedAs('quantity - reserved_quantity')->comment('الكمية المتوفرة');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->decimal('selling_price', 12, 3)->default(0)->comment('سعر البيع');
            $table->string('rack_location', 100)->nullable()->comment('موقع الرف');
            $table->uuid('supplier_id')->nullable()->comment('معرف المورد');
            $table->uuid('purchase_order_id')->nullable()->comment('معرف أمر الشراء');
            $table->uuid('grn_id')->nullable()->comment('معرف إذن الاستلام');
            $table->string('status', 50)->default('available')->comment('الحالة');
            $table->timestamp('received_at')->nullable()->comment('تاريخ الاستلام');
            $table->timestamp('last_moved_at')->nullable()->comment('آخر حركة');

            // Foreign Keys
            $table->foreign('company_id', 'fk_inventory_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_inventory_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_inventory_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('supplier_id', 'fk_inventory_supplier_id')
                  ->references('id')->on('suppliers')
                  ->onDelete('set null');
            $table->foreign('purchase_order_id', 'fk_inventory_po_id')
                  ->references('id')->on('purchase_orders')
                  ->onDelete('set null');
            $table->foreign('grn_id', 'fk_inventory_grn_id')
                  ->references('id')->on('goods_received_notes')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'branch_id', 'product_id', 'batch_number'], 'uniq_inventory_batch_branch_product');

            // Indexes
            $table->index(['company_id', 'branch_id', 'product_id'], 'idx_inventory_company_branch_product');
            $table->index('expiry_date', 'idx_inventory_expiry_date');
            $table->index('batch_number', 'idx_inventory_batch_number');
            $table->index('status', 'idx_inventory_status');
            $table->index('quantity', 'idx_inventory_quantity');

            // Note: Check constraints skipped for SQLite compatibility
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
