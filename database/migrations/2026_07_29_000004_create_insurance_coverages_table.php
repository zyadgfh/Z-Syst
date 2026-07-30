<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('coverage_code')->nullable(); // insurer-side code
            $table->enum('scope', ['product', 'category', 'all'])->default('all');
            $table->decimal('coverage_percent', 5, 2)->default(100.00);
            $table->decimal('copay_percent', 5, 2)->default(0.00);
            $table->decimal('max_amount_per_claim', 12, 2)->nullable();
            $table->decimal('max_amount_per_year', 12, 2)->nullable();
            $table->boolean('requires_preauthorization')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
            $table->index(['insurance_company_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_coverages');
    }
};
