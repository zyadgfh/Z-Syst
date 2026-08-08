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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('company_name');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('license_number')->nullable();

            $table->decimal('rating', 2, 1)->default(0);
            $table->decimal('performance_score', 5, 2)->default(0);

            $table->string('payment_terms')->default('net_30');
            $table->decimal('credit_limit', 10, 2)->default(0);

            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->decimal('rating', 2, 1);
            $table->string('category');
            $table->text('review')->nullable();
            $table->foreignId('rated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->string('contract_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms')->nullable();
            $table->text('conditions')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->decimal('on_time_delivery_rate', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->decimal('price_competitiveness', 5, 2)->default(0);
            $table->decimal('responsiveness', 5, 2)->default(0);

            $table->integer('total_orders')->default(0);
            $table->integer('total_disputes')->default(0);

            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance');
        Schema::dropIfExists('supplier_contracts');
        Schema::dropIfExists('supplier_ratings');
        Schema::dropIfExists('suppliers');
    }
};
