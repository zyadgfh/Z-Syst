<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journal_entries')) {
            return;
        }
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->string('entry_number', 50)->unique()->comment('رقم القيد');
            $table->date('entry_date')->comment('تاريخ القيد');
            $table->text('description')->nullable()->comment('الوصف');
            $table->string('reference_type', 100)->nullable()->comment('نوع المرجع');
            $table->uuid('reference_id')->nullable()->comment('معرف المرجع');
            $table->decimal('total_debit', 15, 3)->default(0)->comment('إجمالي المدين');
            $table->decimal('total_credit', 15, 3)->default(0)->comment('إجمالي الدائن');
            $table->string('status', 50)->default('draft')->comment('الحالة');
            $table->uuid('created_by')->nullable()->comment('منشئ');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id', 'fk_journal_entries_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('created_by', 'fk_journal_entries_created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Indexes
            $table->index('company_id', 'idx_journal_entries_company_id');
            $table->index('entry_date', 'idx_journal_entries_entry_date');
            $table->index('status', 'idx_journal_entries_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};