<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('journal_entry_lines')) {
            return;
        }
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('journal_entry_id')->comment('معرف القيد');
            $table->uuid('account_id')->comment('معرف الحساب');
            $table->text('description')->nullable()->comment('الوصف');
            $table->decimal('debit', 15, 3)->default(0)->comment('مدين');
            $table->decimal('credit', 15, 3)->default(0)->comment('دائن');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('journal_entry_id', 'fk_journal_entry_lines_entry_id')
                  ->references('id')->on('journal_entries')
                  ->onDelete('cascade');
            $table->foreign('account_id', 'fk_journal_entry_lines_account_id')
                  ->references('id')->on('accounts')
                  ->onDelete('cascade');

            // Indexes
            $table->index('journal_entry_id', 'idx_journal_entry_lines_entry_id');
            $table->index('account_id', 'idx_journal_entry_lines_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};