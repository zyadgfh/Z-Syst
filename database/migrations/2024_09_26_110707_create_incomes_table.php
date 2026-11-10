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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->double('amount', 10, 2);
            $table->foreignId('income_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('income_for')->nullable();
            $table->string('payment_type')->default('Cash');
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('income_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
