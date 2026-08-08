<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('reference')->nullable();
            $table->string('bank_reference')->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->date('scheduled_date');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminders_sent')->default(false);
            $table->timestamps();
        });

        Schema::create('aging_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->date('report_date');
            $table->decimal('period_30', 10, 2)->default(0);
            $table->decimal('period_60', 10, 2)->default(0);
            $table->decimal('period_90', 10, 2)->default(0);
            $table->decimal('period_90_plus', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aging_reports');
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('supplier_payments');
    }
};
