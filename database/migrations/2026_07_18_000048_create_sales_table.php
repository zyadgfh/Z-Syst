<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('cash_register_id')->nullable()->comment('معرف الصندوق');
            $table->uuid('customer_id')->nullable()->comment('معرف العميل');
            $table->uuid('prescription_id')->nullable()->comment('معرف الوصفة');
            $table->uuid('user_id')->comment('معرف المستخدم');
            $table->string('invoice_number', 50)->comment('رقم الفاتورة');
            $table->timestamp('invoice_date')->comment('تاريخ الفاتورة');
            $table->string('sale_type', 50)->default('walk_in')->comment('نوع البيع');
            $table->decimal('subtotal', 15, 3)->default(0)->comment('الإجمالي الفرعي');
            $table->decimal('discount_amount', 15, 3)->default(0)->comment('مبلغ الخصم');
            $table->string('discount_type', 20)->default('fixed')->comment('نوع الخصم');
            $table->uuid('coupon_id')->nullable()->comment('معرف الكوبون');
            $table->decimal('tax_amount', 15, 3)->default(0)->comment('مبلغ الضريبة');
            $table->decimal('total_amount', 15, 3)->default(0)->comment('الإجمالي');
            $table->decimal('amount_paid', 15, 3)->default(0)->comment('المبلغ المدفوع');
            $table->decimal('change_amount', 15, 3)->default(0)->comment('الباقي');
            $table->decimal('due_amount', 15, 3)->default(0)->comment('المستحق');
            $table->string('payment_method', 50)->default('cash')->comment('طريقة الدفع');
            $table->string('payment_status', 50)->default('paid')->comment('حالة الدفع');
            $table->integer('items_count')->default(0)->comment('عدد البنود');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->jsonb('metadata')->default('{}')->comment('بيانات إضافية');
            $table->string('status', 50)->default('completed')->comment('الحالة');
            $table->uuid('voided_by')->nullable()->comment('ألغي بواسطة');
            $table->timestamp('voided_at')->nullable()->comment('تاريخ الإلغاء');
            $table->text('void_reason')->nullable()->comment('سبب الإلغاء');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_sales_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_sales_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('cash_register_id', 'fk_sales_cash_register_id')
                  ->references('id')->on('cash_registers')
                  ->onDelete('set null');
            $table->foreign('customer_id', 'fk_sales_customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('set null');
            $table->foreign('prescription_id', 'fk_sales_prescription_id')
                  ->references('id')->on('prescriptions')
                  ->onDelete('set null');
            $table->foreign('user_id', 'fk_sales_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('coupon_id', 'fk_sales_coupon_id')
                  ->references('id')->on('coupons')
                  ->onDelete('set null');
            $table->foreign('voided_by', 'fk_sales_voided_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Unique Constraints
            $table->unique(['branch_id', 'invoice_number'], 'uniq_sales_invoice_number_branch');

            // Indexes
            $table->index('company_id', 'idx_sales_company_id');
            $table->index('branch_id', 'idx_sales_branch_id');
            $table->index('customer_id', 'idx_sales_customer_id');
            $table->index('user_id', 'idx_sales_user_id');
            $table->index('invoice_date', 'idx_sales_invoice_date');
            $table->index('status', 'idx_sales_status');
            $table->index('payment_status', 'idx_sales_payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};