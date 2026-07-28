<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_registers')) {
            return;
        }
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->comment('معرف الفرع');
            $table->uuid('user_id')->comment('معرف المستخدم');
            $table->string('register_number', 50)->comment('رقم الصندوق');
            $table->decimal('opening_balance', 15, 3)->default(0)->comment('رصيد الافتتاح');
            $table->decimal('closing_balance', 15, 3)->default(0)->comment('رصيد الإغلاق');
            $table->decimal('expected_balance', 15, 3)->default(0)->comment('الرصيد المتوقع');
            $table->decimal('actual_balance', 15, 3)->default(0)->comment('الرصيد الفعلي');
            $table->decimal('difference', 15, 3)->default(0)->comment('الفرق');
            $table->decimal('total_sales', 15, 3)->default(0)->comment('إجمالي المبيعات');
            $table->decimal('total_returns', 15, 3)->default(0)->comment('إجمالي المرتجعات');
            $table->decimal('total_expenses', 15, 3)->default(0)->comment('إجمالي المصروفات');
            $table->decimal('total_cash_in', 15, 3)->default(0)->comment('إجمالي الإيداعات');
            $table->decimal('total_cash_out', 15, 3)->default(0)->comment('إجمالي المسحوبات');
            $table->string('status', 20)->default('open')->comment('الحالة');
            $table->timestamp('opened_at')->nullable()->comment('تاريخ الفتح');
            $table->timestamp('closed_at')->nullable()->comment('تاريخ الإغلاق');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_cash_registers_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_cash_registers_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('cascade');
            $table->foreign('user_id', 'fk_cash_registers_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Unique Constraints
            $table->unique(['branch_id', 'register_number'], 'uniq_cash_registers_number_branch');

            // Indexes
            $table->index('company_id', 'idx_cash_registers_company_id');
            $table->index('branch_id', 'idx_cash_registers_branch_id');
            $table->index('status', 'idx_cash_registers_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};