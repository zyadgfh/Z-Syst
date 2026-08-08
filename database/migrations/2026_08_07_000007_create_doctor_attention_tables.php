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
        Schema::create('doctor_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medical_rep_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->date('activity_date');
            $table->integer('referral_count')->default(0);
            $table->decimal('referral_amount', 10, 2)->default(0);
            $table->integer('prescription_count')->default(0);
            
            $table->json('details')->nullable(); // Additional activity details
            
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index('doctor_id');
            $table->index('medical_rep_id');
            $table->index('activity_date');
            $table->unique(['doctor_id', 'activity_date', 'business_id']);
        });

        Schema::create('doctor_attention_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            $table->date('calculated_date');
            $table->decimal('attention_score', 5, 2)->default(100); // 0-100, lower = needs attention
            $table->decimal('decline_percentage', 5, 2)->default(0); // Percentage drop from baseline
            $table->integer('days_inactive')->default(0); // Days since last referral
            $table->date('last_referral_date')->nullable();
            
            $table->decimal('baseline_referrals', 8, 2)->default(0); // Average referrals per period
            $table->integer('current_period_referrals')->default(0);
            $table->integer('previous_period_referrals')->default(0);
            
            $table->string('status')->default('active'); // active, needs_attention, critical
            $table->text('alert_reason')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index('doctor_id');
            $table->index('calculated_date');
            $table->index('status');
            $table->index('attention_score');
        });

        Schema::create('doctor_attention_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medical_rep_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('alert_type'); // referral_drop, inactivity, critical
            $table->string('severity'); // low, medium, high, critical
            $table->text('message');
            $table->json('details')->nullable();
            
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->boolean('action_taken')->default(false);
            $table->text('action_details')->nullable();
            $table->timestamp('action_taken_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['business_id', 'branch_id']);
            $table->index('doctor_id');
            $table->index('medical_rep_id');
            $table->index('alert_type');
            $table->index('severity');
            $table->index('is_sent');
            $table->index('is_read');
        });

        Schema::create('doctor_attention_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            
            // Referral drop thresholds
            $table->decimal('referral_drop_threshold', 5, 2)->default(30); // 30% drop triggers alert
            $table->integer('referral_drop_period_days')->default(30); // Compare last 30 days
            
            // Inactivity thresholds
            $table->integer('inactivity_threshold_days')->default(14); // 14 days no referrals triggers alert
            $table->integer('critical_inactivity_days')->default(30); // 30 days no referrals = critical
            
            // Attention score thresholds
            $table->decimal('attention_score_warning', 5, 2)->default(60); // Below 60 = warning
            $table->decimal('attention_score_critical', 5, 2)->default(30); // Below 30 = critical
            
            // Notification settings
            $table->boolean('enable_push_notifications')->default(true);
            $table->boolean('enable_email_notifications')->default(false);
            $table->boolean('enable_sms_notifications')->default(false);
            
            // Notification recipients
            $table->json('notify_roles')->nullable(); // ['owner', 'medical_rep']
            $table->json('notify_users')->nullable(); // Specific user IDs
            
            // Frequency
            $table->integer('alert_frequency_hours')->default(24); // Don't alert more than once per 24h per doctor
            
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_attention_settings');
        Schema::dropIfExists('doctor_attention_alerts');
        Schema::dropIfExists('doctor_attention_scores');
        Schema::dropIfExists('doctor_activities');
    }
};
