<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_transfers')) {
            return;
        }
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('transfer_number', 50)->unique()->comment('رقم التحويل');
            $table->uuid('from_branch_id')->comment('من فرع');
            $table->uuid('to_branch_id')->comment('إلى فرع');
            $table->string('status', 50)->default('pending')->comment('الحالة');
            $table->uuid('requested_by')->comment('مطلوب بواسطة');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->timestamp('shipped_at')->nullable()->comment('تاريخ الشحن');
            $table->timestamp('received_at')->nullable()->comment('تاريخ الاستلام');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_stock_transfers_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('from_branch_id', 'fk_stock_transfers_from_branch')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('to_branch_id', 'fk_stock_transfers_to_branch')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('requested_by', 'fk_stock_transfers_requested_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('approved_by', 'fk_stock_transfers_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_stock_transfers_company_id');
            $table->index('from_branch_id', 'idx_stock_transfers_from_branch_id');
            $table->index('to_branch_id', 'idx_stock_transfers_to_branch_id');
            $table->index('status', 'idx_stock_transfers_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};