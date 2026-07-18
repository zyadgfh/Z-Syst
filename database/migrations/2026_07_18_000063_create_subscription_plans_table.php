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
            $table->uuid('id')->primary();
            $table->string('name', 255)->comment('اسم الخطة');
            $table->string('slug', 100)->unique()->comment('slug الخطة');
            $table->text('description')->nullable()->comment('الوصف');
            $table->decimal('price_monthly', 10, 3)->default(0)->comment('السعر الشهري');
            $table->decimal('price_yearly', 10, 3)->default(0)->comment('السعر السنوي');
            $table->string('currency', 3)->default('USD')->comment('العملة');
            $table->integer('trial_days')->default(14)->comment('أيام التجربة');
            $table->integer('max_branches')->nullable()->comment('أقصى عدد الفروع');
            $table->integer('max_users')->nullable()->comment('أقصى عدد المستخدمين');
            $table->integer('max_products')->nullable()->comment('أقصى عدد المنتجات');
            $table->integer('max_invoices_monthly')->nullable()->comment('أقصى عدد الفواتير');
            $table->jsonb('features')->nullable()->comment('الميزات');
            $table->boolean('is_active')->default(true)->comment('نشط');
            $table->integer('sort_order')->default(0)->comment('الترتيب');
            $table->timestamps();

            // Unique Constraints
            $table->unique('slug', 'uniq_subscription_plans_slug');

            // Indexes
            $table->index('is_active', 'idx_subscription_plans_is_active');
            $table->index('price_monthly', 'idx_subscription_plans_price_monthly');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};