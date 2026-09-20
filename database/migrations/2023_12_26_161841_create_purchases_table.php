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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discountAmount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('dueAmount', 10, 2)->default(0);
            $table->decimal('paidAmount', 10, 2)->default(0);
            $table->decimal('totalAmount', 10, 2)->default(0);
            $table->string('invoiceNumber')->nullable();
            $table->boolean('isPaid')->default(0);
            $table->string('paymentType')->default('Cash');
            $table->timestamp('purchaseDate')->nullable();
            $table->longText('purchase_data')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
