<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('product_id')->comment('معرف المنتج');
            $table->string('batch_number', 100)->nullable()->comment('رقم الدفعة');
            $table->string('movement_type', 50)->comment('نوع الحركة');
            $table->string('reference_type', 100)->nullable()->comment('نوع المرجع');
            $table->uuid('reference_id')->nullable()->comment('معرف المرجع');
            $table->decimal('quantity', 15, 3)->comment('الكمية');
            $table->decimal('quantity_before', 15, 3)->comment('الكمية قبل');
            $table->decimal('quantity_after', 15, 3)->comment('الكمية بعد');
            $table->decimal('cost_price', 12, 3)->default(0)->comment('سعر التكلفة');
            $table->decimal('unit_cost', 12, 3)->default(0)->comment('تكلفة الوحدة');
            $table->decimal('total_cost', 15, 3)->default(0)->comment('التكلفة الإجمالية');
            $table->uuid('from_branch_id')->nullable()->comment('من فرع');
            $table->uuid('to_branch_id')->nullable()->comment('إلى فرع');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->uuid('performed_by')->comment('منفذ بواسطة');
            $table->timestamp('performed_at')->useCurrent()->comment('تاريخ التنفيذ');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_stock_movements_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_stock_movements_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('product_id', 'fk_stock_movements_product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
            $table->foreign('from_branch_id', 'fk_stock_movements_from_branch')
                  ->references('id')->on('branches')
                  ->onDelete('set null');
            $table->foreign('to_branch_id', 'fk_stock_movements_to_branch')
                  ->references('id')->on('branches')
                  ->onDelete('set null');
            $table->foreign('performed_by', 'fk_stock_movements_performed_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Indexes
            $table->index(['company_id', 'branch_id'], 'idx_stock_movements_company_branch');
            $table->index('product_id', 'idx_stock_movements_product');
            $table->index('movement_type', 'idx_stock_movements_movement_type');
            $table->index('performed_at', 'idx_stock_movements_performed_at');
            $table->index(['reference_type', 'reference_id'], 'idx_stock_movements_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};