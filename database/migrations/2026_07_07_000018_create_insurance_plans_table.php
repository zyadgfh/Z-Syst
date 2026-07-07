<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('coverage_percentage', 5, 2)->default(0);
            $table->decimal('max_coverage', 12, 2)->nullable();
            $table->decimal('annual_limit', 12, 2)->nullable();
            $table->decimal('co_pay', 5, 2)->default(0);
            $table->boolean('requires_pre_approval')->default(false);
            $table->text('covered_items')->nullable();
            $table->text('exclusions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('insurance_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_plans');
    }
};