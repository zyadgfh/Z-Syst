<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            return;
        }
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('supplier_id')->comment('معرف المورد');
            $table->string('po_number', 50)->comment('رقم أمر الشراء');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->date('order_date')->comment('تاريخ الأمر');
            $table->date('expected_delivery_date')->nullable()->comment('التوقعات');
            $table->date('actual_delivery_date')->nullable()->comment('تاريخ التسليم');
            $table->decimal('subtotal', 15, 3)->default(0)->comment('الإجمالي الفرعي');
            $table->decimal('discount_amount', 15, 3)->default(0)->comment('مبلغ الخصم');
            $table->string('discount_type', 20)->default('fixed')->comment('نوع الخصم');
            $table->decimal('tax_amount', 15, 3)->default(0)->comment('مبلغ الضريبة');
            $table->decimal('shipping_cost', 15, 3)->default(0)->comment('تكلفة الشحن');
            $table->decimal('total_amount', 15, 3)->default(0)->comment('الإجمالي');
            $table->decimal('paid_amount', 15, 3)->default(0)->comment('المدفوع');
            $table->decimal('due_amount', 15, 3)->default(0)->comment('المستحق');
            $table->string('payment_status', 50)->default('unpaid')->comment('حالة الدفع');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ التأييد');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->uuid('updated_by')->nullable()->comment('محدث');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_purchase_orders_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_purchase_orders_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('supplier_id', 'fk_purchase_orders_supplier_id')
                  ->references('id')->on('suppliers')
                  ->onDelete('cascade');
            $table->foreign('approved_by', 'fk_purchase_orders_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_purchase_orders_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('updated_by', 'fk_purchase_orders_updated_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['company_id', 'po_number'], 'uniq_purchase_orders_po_number_company');

            // Indexes
            $table->index('company_id', 'idx_purchase_orders_company_id');
            $table->index('branch_id', 'idx_purchase_orders_branch_id');
            $table->index('supplier_id', 'idx_purchase_orders_supplier_id');
            $table->index('status', 'idx_purchase_orders_status');
            $table->index('order_date', 'idx_purchase_orders_order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};