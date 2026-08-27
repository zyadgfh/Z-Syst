<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Account Types (Assets, Liabilities, Equity, Revenue, Expenses)
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);           // e.g. 'Asset', 'Liability', 'Equity', 'Revenue', 'Expense'
            $table->string('code', 10);            // e.g. 'A', 'L', 'E', 'R', 'X'
            $table->boolean('is_debit_positive')->default(true); // Assets & Expenses = debit-positive
            $table->timestamps();

            $table->unique(['name']);
        });

        // Chart of Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->string('code', 20);            // e.g. '1000', '1010', '2000', '4000'
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);  // system accounts cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active']);
        });

        // Journal Entries (header)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_number', 30);    // e.g. JE-20260824-001
            $table->date('entry_date');
            $table->string('reference_type', 100)->nullable();  // polymorphic: Sale, Purchase, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'entry_date']);
            $table->index(['business_id', 'entry_number']);
            $table->index(['business_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Journal Entry Lines (debits and credits)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id']);
            $table->index(['account_id']);
        });

        // General Ledger (running balance per account per period)
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('journal_entry_id')->constrained('journal_entries');
            $table->foreignId('journal_entry_line_id')->constrained('journal_entry_lines');
            $table->date('transaction_date');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);  // running balance
            $table->timestamps();

            $table->index(['business_id', 'account_id', 'transaction_date']);
            $table->index(['business_id', 'transaction_date']);
        });

        // Fiscal Periods
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);           // e.g. 'FY2026'
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'name']);
            $table->index(['business_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
        Schema::dropIfExists('general_ledger');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_types');
    }
};
