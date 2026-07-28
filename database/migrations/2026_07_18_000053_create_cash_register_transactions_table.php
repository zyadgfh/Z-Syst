<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            return;
        }
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cash_register_id')->comment('معرف الصندوق');
            $table->string('transaction_type', 50)->comment('نوع المعاملة');
            $table->string('reference_type', 100)->nullable()->comment('نوع المرجع');
            $table->uuid('reference_id')->nullable()->comment('معرف المرجع');
            $table->decimal('amount', 15, 3)->default(0)->comment('المبلغ');
            $table->decimal('balance_after', 15, 3)->default(0)->comment('الرصيد بعد');
            $table->text('description')->nullable()->comment('الوصف');
            $table->uuid('performed_by')->comment('منفذ بواسطة');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('cash_register_id', 'fk_cash_register_transactions_register_id')
                  ->references('id')->on('cash_registers')
                  ->onDelete('cascade');
            $table->foreign('performed_by', 'fk_cash_register_transactions_performed_by')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Indexes
            $table->index('cash_register_id', 'idx_cash_register_transactions_register_id');
            $table->index('transaction_type', 'idx_cash_register_transactions_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};