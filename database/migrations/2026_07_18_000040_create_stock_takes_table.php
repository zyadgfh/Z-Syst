<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_takes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->string('stock_take_number', 50)->unique()->comment('رقم الجرد');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->date('start_date')->nullable()->comment('تاريخ البدء');
            $table->date('end_date')->nullable()->comment('تاريخ الانتهاء');
            $table->uuid('completed_by')->nullable()->comment('مكتمل بواسطة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_stock_takes_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_stock_takes_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('completed_by', 'fk_stock_takes_completed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_stock_takes_company_id');
            $table->index('branch_id', 'idx_stock_takes_branch_id');
            $table->index('status', 'idx_stock_takes_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_takes');
    }
};