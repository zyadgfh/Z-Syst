<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_type'); // purchase, purchase_return, payment, adjustment
            $table->string('reference_type')->nullable(); // Purchase, PurchaseReturn, SupplierPayment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('debit', 12, 2)->default(0); // amount owed TO supplier
            $table->decimal('credit', 12, 2)->default(0); // amount paid/credited TO supplier
            $table->decimal('balance_after', 12, 2)->default(0); // running balance after this transaction
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'party_id']);
            $table->index(['party_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
    }
};
