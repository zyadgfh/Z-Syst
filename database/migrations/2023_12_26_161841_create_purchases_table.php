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
            $table->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->double('discount_amount', 10, 2)->default(0);
            $table->double('tax_amount', 10, 2)->default(0);
            $table->double('due_amount', 10, 2)->default(0);
            $table->double('paid_amount', 10, 2)->default(0);
            $table->double('total_amount', 10, 2)->default(0);
            $table->string('invoice_number')->nullable();
            $table->boolean('is_paid')->default(0);
            $table->string('payment_type')->default('Cash');
            $table->timestamp('purchase_date')->nullable();
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
