<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->decimal('amount_paid', 15, 2)->default(0);
                $table->decimal('change_amount', 15, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_status')->default('pending');
                $table->string('sale_type')->default('walk-in');
                $table->unsignedBigInteger('prescription_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->timestamps();
            });

            // Add FK to prescriptions only if table exists (avoid ordering issues)
            if (Schema::hasTable('prescriptions')) {
                Schema::table('sales', function (Blueprint $table) {
                    $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('set null');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
