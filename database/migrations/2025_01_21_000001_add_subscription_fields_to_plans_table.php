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
        // Update plans table to include rate_limit and feature limits
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('rate_limit')->default(100)->comment('Requests per minute');
            $table->boolean('is_default')->default(false);
        });

        // Ensure subscription_plans table exists with proper columns
        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('currency', 3)->default('EGP');
                $table->integer('duration_days')->default(30);
                $table->integer('rate_limit')->default(100);
                $table->integer('max_branches')->default(1);
                $table->integer('max_products')->default(100);
                $table->integer('max_users')->default(5);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Ensure plan_subscribes table has proper columns
        if (Schema::hasTable('plan_subscribes')) {
            Schema::table('plan_subscribes', function (Blueprint $table) {
                // Make company_id nullable for now (legacy support)
                if (! Schema::hasColumn('plan_subscribes', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['rate_limit', 'is_default']);
        });

        if (Schema::hasTable('subscription_plans')) {
            Schema::dropIfExists('subscription_plans');
        }
    }
};