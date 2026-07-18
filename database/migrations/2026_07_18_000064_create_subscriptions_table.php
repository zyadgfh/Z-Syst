<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->comment('معرف الشركة');
            $table->uuid('plan_id')->comment('معرف الخطة');
            $table->string('status', 50)->default('active')->comment('الحالة');
            $table->string('billing_cycle', 20)->default('monthly')->comment('دورة الفوترة');
            $table->timestamp('current_period_start')->nullable()->comment('بدء الفترة');
            $table->timestamp('current_period_end')->nullable()->comment('نهاية الفترة');
            $table->timestamp('trial_ends_at')->nullable()->comment('نهاية التجربة');
            $table->timestamp('cancelled_at')->nullable()->comment('تاريخ الإلغاء');
            $table->timestamp('paused_at')->nullable()->comment('تاريخ الإيقاف');
            $table->string('stripe_subscription_id', 255)->nullable()->comment('معرف Stripe');
            $table->string('stripe_customer_id', 255)->nullable()->comment('معرف عميل Stripe');
            $table->string('payment_method', 50)->default('manual')->comment('طريقة الدفع');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign('company_id', 'fk_subscriptions_company_id')
                  ->references('id')->on('companies')
                  ->onDelete('cascade');
            $table->foreign('plan_id', 'fk_subscriptions_plan_id')
                  ->references('id')->on('subscription_plans')
                  ->onDelete('cascade');

            // Indexes
            $table->index('company_id', 'idx_subscriptions_company_id');
            $table->index('plan_id', 'idx_subscriptions_plan_id');
            $table->index('status', 'idx_subscriptions_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};