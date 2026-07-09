<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->integer('max_users')->nullable();
            $table->integer('max_branches')->nullable();
            $table->integer('max_products')->nullable();
            $table->integer('max_transactions_monthly')->nullable();
            $table->boolean('has_advanced_reports')->default(false);
            $table->boolean('has_insurance_integration')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
        });

        // Subscription features pivot
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->string('feature_value')->nullable();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
