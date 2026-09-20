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
        Schema::create('financial_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('audit_number')->unique();
            $table->enum('audit_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('opening_balance')->default(0);
            $table->decimal('total_revenue')->default(0);
            $table->decimal('total_expenses')->default(0);
            $table->decimal('closing_balance')->default(0);
            $table->decimal('variance')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_audit_logs');
    }
};
