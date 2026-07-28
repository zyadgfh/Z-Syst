<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            return;
        }
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('branch_id')->nullable()->comment('معرف الفرع');
            $table->uuid('expense_category_id')->nullable()->comment('معرف الفئة');
            $table->string('expense_number', 50)->unique()->comment('رقم المصروف');
            $table->date('expense_date')->comment('تاريخ المصروف');
            $table->decimal('amount', 15, 3)->default(0)->comment('المبلغ');
            $table->text('description')->nullable()->comment('الوصف');
            $table->string('receipt_path', 500)->nullable()->comment('مسار الإيصال');
            $table->string('payment_method', 50)->nullable()->comment('طريقة الدفع');
            $table->string('reference_number', 255)->nullable()->comment('رقم المرجع');
            $table->uuid('approved_by')->nullable()->comment('معتمد بواسطة');
            $table->timestamp('approved_at')->nullable()->comment('تاريخ التأييد');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_expenses_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('branch_id', 'fk_expenses_branch_id')
                  ->references('id')->on('branches')
                  ->onDelete('set null');
            $table->foreign('expense_category_id', 'fk_expenses_category_id')
                  ->references('id')->on('expense_categories')
                  ->onDelete('set null');
            $table->foreign('approved_by', 'fk_expenses_approved_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
            $table->foreign('created_by', 'fk_expenses_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_expenses_company_id');
            $table->index('branch_id', 'idx_expenses_branch_id');
            $table->index('expense_category_id', 'idx_expenses_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};