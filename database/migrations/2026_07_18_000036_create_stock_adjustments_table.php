<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('adjustment_number', 50)->unique()->comment('رقم التسوية');
            $table->string('adjustment_type', 50)->comment('نوع التسوية');
            $table->text('reason')->nullable()->comment('السبب');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->integer('total_items')->default(0)->comment('إجمالي البنود');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ التأييد');
            $table->uuid('applied_by')->nullable()->comment('طبق بواسطة');
            $table->timestamp('applied_at')->nullable()->comment('تاريخ التطبيق');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_stock_adjustments_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_stock_adjustments_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('approved_by', 'fk_stock_adjustments_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('applied_by', 'fk_stock_adjustments_applied_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_stock_adjustments_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_stock_adjustments_company_id');
            $table->index('branch_id', 'idx_stock_adjustments_branch_id');
            $table->index('status', 'idx_stock_adjustments_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};