<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_returns')) {
            return;
        }
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('return_number', 50)->unique()->comment('رقم المرتجع');
            $table->uuid('supplier_id')->comment('معرف المورد');
            $table->uuid('purchase_order_id')->nullable()->comment('معرف أمر الشراء');
            $table->uuid('grn_id')->nullable()->comment('معرف إذن الاستلام');
            $table->date('return_date')->comment('تاريخ المرتجع');
            $table->text('reason')->nullable()->comment('السبب');
            $table->decimal('subtotal', 15, 3)->default(0)->comment('الإجمالي الفرعي');
            $table->decimal('tax', 15, 3)->default(0)->comment('الضريبة');
            $table->decimal('total', 15, 3)->default(0)->comment('الإجمالي');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_purchase_returns_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_purchase_returns_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('supplier_id', 'fk_purchase_returns_supplier_id')
                  ->references('id')->on('suppliers')
                  ->onDelete('cascade');
            $table->foreign('purchase_order_id', 'fk_purchase_returns_po_id')
                  ->references('id')->on('purchase_orders')
                  ->onDelete('set null');
            $table->foreign('grn_id', 'fk_purchase_returns_grn_id')
                  ->references('id')->on('goods_received_notes')
                  ->onDelete('set null');
            $table->foreign('approved_by', 'fk_purchase_returns_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_purchase_returns_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_purchase_returns_company_id');
            $table->index('branch_id', 'idx_purchase_returns_branch_id');
            $table->index('supplier_id', 'idx_purchase_returns_supplier_id');
            $table->index('status', 'idx_purchase_returns_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};