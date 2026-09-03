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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('type', ['revenue', 'expense'])->default('revenue');
            $table->decimal('amount', 10, 2)->default(0);
            
            $table->string('reference_type')->nullable(); // sale, purchase, expense, income, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->string('category')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
            $table->index('transaction_date');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};