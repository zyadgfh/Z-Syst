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

            // The application's tenant column on this table is `tenant_id`, below:
            // the model's fillable, its belongsTo(Business::class, 'tenant_id') and
            // every query in TenantPaymentSettingController use that name. This
            // second column exists only because the database enforces tenancy
            // independently of Eloquent — tenant_payment_settings has Row Level
            // Security enabled with four policies of the form
            // `business_id = private.current_business_id()`. Production declares it
            // nullable with no foreign key; reproducing it here keeps
            // `migrate:fresh` identical to production so those policies can be
            // created on a fresh database. Do not "tidy" this into tenant_id
            // without migrating the RLS policies with it.
            $table->unsignedBigInteger('business_id')->nullable();

            $table->foreignId('tenant_id')->constrained('businesses')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
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
