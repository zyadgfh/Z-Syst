<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('return_number', 50)->unique()->comment('رقم المرتجع');
            $table->uuid('sale_id')->comment('معرف البيع');
            $table->uuid('customer_id')->nullable()->comment('معرف العميل');
            $table->uuid('user_id')->comment('معرف المستخدم');
            $table->date('return_date')->comment('تاريخ المرتجع');
            $table->text('reason')->nullable()->comment('السبب');
            $table->decimal('subtotal', 15, 3)->default(0)->comment('الإجمالي الفرعي');
            $table->decimal('discount', 15, 3)->default(0)->comment('الخصم');
            $table->decimal('tax', 15, 3)->default(0)->comment('الضريبة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->string('refund_method', 50)->default('cash')->comment('طريقة الاسترداد');
            $table->string('refund_status', 50)->default('pending')->comment('حالة الاسترداد');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->uuid('processed_by')->nullable()->comment('معالج بواسطة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_sale_returns_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_sale_returns_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('sale_id', 'fk_sale_returns_sale_id')
                  ->references('id')->on('sales')
                  ->onDelete('cascade');
            $table->foreign('customer_id', 'fk_sale_returns_customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('set null');
            $table->foreign('user_id', 'fk_sale_returns_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('approved_by', 'fk_sale_returns_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('processed_by', 'fk_sale_returns_processed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_sale_returns_company_id');
            $table->index('branch_id', 'idx_sale_returns_branch_id');
            $table->index('sale_id', 'idx_sale_returns_sale_id');
            $table->index('refund_status', 'idx_sale_returns_refund_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};