<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            // Order info
            $table->string('order_number')->unique();
            $table->string('status')->default('pending'); // pending, confirmed, processing, shipped, delivered, cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, partially_refunded, refunded
            $table->string('payment_method')->nullable(); // cash_on_delivery, online, bank_transfer

            // Customer info (snapshot at time of order)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('city')->nullable();
            $table->string('notes')->nullable();

            // Pricing
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Metadata
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
