<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // sale, purchase, income, expense, due_pay / due_collect, bank, cash, cheque, sale_return, purchase_return, payroll
            $table->string('transaction_type'); // cash_payment, cheque_payment, wallet_payment, bank_payment, bank_to_bank, bank_to_cash, cash_to_bank, adjust_bank, adjust_cash, cheque_to_bank, cheque_to_cash
            $table->string('type')->nullable(); // debit, credit, transfer, pending, deposit, others
            $table->double('amount', 15, 3);
            $table->date('date')->nullable();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_bank')->nullable()->constrained('payment_types')->nullOnDelete();
            $table->foreignId('to_bank')->nullable()->constrained('payment_types')->nullOnDelete();
            $table->unsignedBigInteger('reference_id')->nullable(); // sale_id, purchase_id, due_collect_id, income_id, expense_id, payroll_id
            $table->string('invoice_no')->nullable(); // related invoice no
            $table->string('image')->nullable();
            $table->text('note')->nullable(); // description/note
            $table->longText('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

