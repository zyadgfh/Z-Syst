<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sale_id')->comment('معرف البيع');
            $table->string('payment_method', 50)->comment('طريقة الدفع');
            $table->decimal('amount', 15, 3)->default(0)->comment('المبلغ');
            $table->string('reference_number', 255)->nullable()->comment('رقم المرجع');
            $table->string('card_last_four', 4)->nullable()->comment('آخر 4 أرقام البطاقة');
            $table->string('transaction_id', 255)->nullable()->comment('رقم المعاملة');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('sale_id', 'fk_sale_payments_sale_id')
                  ->references('id')->on('sales')
                  ->onDelete('cascade');

            // Indexes
            $table->index('sale_id', 'idx_sale_payments_sale_id');
            $table->index('payment_method', 'idx_sale_payments_payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};