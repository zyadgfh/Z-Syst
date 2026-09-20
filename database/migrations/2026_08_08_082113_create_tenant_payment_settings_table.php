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
        Schema::create('tenant_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('businesses')->onDelete('cascade');
            $table->bigInteger('branch_id')->nullable();
            $table->foreignId('gateway_id')->constrained('gateways')->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Egyptian payment gateway specific fields
            $table->string('merchant_phone')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('merchant_key')->nullable();
            $table->string('merchant_instapay_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_name')->nullable();
            
            $table->timestamps();
            
            // Unique constraint for tenant-branch-gateway combination
            $table->unique(['tenant_id', 'branch_id', 'gateway_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_settings');
    }
};
