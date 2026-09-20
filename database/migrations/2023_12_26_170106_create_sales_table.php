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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discountAmount', 10, 2)->default(0);
            $table->decimal('dueAmount', 10, 2)->default(0);
            $table->boolean('isPaid')->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('paidAmount', 10, 2)->default(0);
            $table->decimal('totalAmount', 10, 2)->default(0);
            $table->decimal('lossProfit', 10, 2)->default(0);
            $table->string('paymentType')->nullable();
            $table->string('invoiceNumber')->nullable();
            $table->timestamp('saleDate')->nullable();
            $table->longText('sale_data')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
