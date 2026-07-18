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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name'); // Vodafone Cash
            $table->string('code')->unique(); // vodafone_cash
            $table->string('gateway')->default('paymob');
            $table->string('gateway_integration_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pos_enabled')->default(true);
            $table->boolean('is_online_enabled')->default(true);
            $table->decimal('min_amount', 12, 2)->default(1);
            $table->decimal('max_amount', 12, 2)->default(50000);
            $table->json('configuration')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_pos_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};