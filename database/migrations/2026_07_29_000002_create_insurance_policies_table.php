<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('policy_number')->unique();
            $table->string('member_id')->nullable();
            $table->string('card_number')->nullable();
            $table->string('holder_name');
            $table->date('holder_dob')->nullable();
            $table->enum('holder_gender', ['male', 'female', 'other'])->nullable();
            $table->string('holder_phone', 50)->nullable();
            $table->string('holder_email')->nullable();
            $table->text('holder_address')->nullable();
            $table->enum('plan_type', ['individual', 'family', 'corporate', 'government'])->default('individual');
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled', 'pending'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('used_amount', 12, 2)->default(0);
            $table->decimal('remaining_limit', 12, 2)->nullable();
            $table->decimal('coverage_percent', 5, 2)->nullable();
            $table->decimal('copay_percent', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'end_date']);
            $table->index('member_id');
            $table->index('card_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
