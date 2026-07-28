<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('goods_received_notes')) {
            return;
        }
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('grn_number', 50)->unique()->comment('رقم إذن الاستلام');
            $table->uuid('purchase_order_id')->nullable()->comment('معرف أمر الشراء');
            $table->uuid('supplier_id')->nullable()->comment('معرف المورد');
            $table->date('received_date')->comment('تاريخ الاستلام');
            $table->uuid('received_by')->comment('مستلم بواسطة');
            $table->uuid('verified_by')->nullable()->comment('موثق بواسطة');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_grn_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_grn_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('purchase_order_id', 'fk_grn_po_id')
                  ->references('id')->on('purchase_orders')
                  ->onDelete('set null');
            $table->foreign('supplier_id', 'fk_grn_supplier_id')
                  ->references('id')->on('suppliers')
                  ->onDelete('set null');
            $table->foreign('received_by', 'fk_grn_received_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('verified_by', 'fk_grn_verified_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_grn_company_id');
            $table->index('branch_id', 'idx_grn_branch_id');
            $table->index('grn_number', 'idx_grn_grn_number');
            $table->index('status', 'idx_grn_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_notes');
    }
};