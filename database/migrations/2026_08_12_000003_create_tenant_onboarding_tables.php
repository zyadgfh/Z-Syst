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
        // Onboarding templates table
        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('steps')->nullable(); // Array of onboarding steps
            $table->json('default_settings')->nullable(); // Default business settings
            $table->json('default_roles')->nullable(); // Default roles to create
            $table->json('default_permissions')->nullable(); // Default permissions
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });

        // Tenant onboarding instances table
        Schema::create('tenant_onboarding_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'failed', 'skipped'])->default('not_started');
            $table->integer('current_step')->default(0);
            $table->integer('total_steps')->default(0);
            $table->json('progress_data')->nullable(); // Progress tracking data
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'status']);
            $table->index('status');
        });

        // Onboarding step tracking table
        Schema::create('onboarding_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_instance_id')->constrained('tenant_onboarding_instances')->cascadeOnDelete();
            $table->string('step_name');
            $table->integer('step_order');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'skipped'])->default('pending');
            $table->json('step_data')->nullable(); // Step-specific data
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['onboarding_instance_id', 'step_order']);
            $table->index('status');
        });

        // Welcome email templates table
        Schema::create('welcome_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->text('content');
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Onboarding checklist items table
        Schema::create('onboarding_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // setup, configuration, training, etc.
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('resources')->nullable(); // Links, videos, documentation
            $table->timestamps();
            
            $table->index(['onboarding_template_id', 'category']);
            $table->index('order');
        });

        // Tenant checklist progress table
        Schema::create('tenant_checklist_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['business_id', 'checklist_item_id']);
            $table->index(['business_id', 'is_completed']);
        });

        // Onboarding automation rules table
        Schema::create('onboarding_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('trigger_event'); // account_created, step_completed, etc.
            $table->json('trigger_conditions')->nullable(); // Conditions to trigger
            $table->string('action_type'); // send_email, create_user, etc.
            $table->json('action_config')->nullable(); // Action configuration
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['trigger_event', 'is_active']);
        });

        // Onboarding analytics table
        Schema::create('onboarding_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->integer('steps_completed')->default(0);
            $table->integer('total_steps')->default(0);
            $table->integer('time_spent_minutes')->default(0);
            $table->json('metrics')->nullable(); // Additional metrics
            $table->timestamps();
            
            $table->unique(['business_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_analytics');
        Schema::dropIfExists('onboarding_automation_rules');
        Schema::dropIfExists('tenant_checklist_progress');
        Schema::dropIfExists('onboarding_checklist_items');
        Schema::dropIfExists('welcome_email_templates');
        Schema::dropIfExists('onboarding_step_logs');
        Schema::dropIfExists('tenant_onboarding_instances');
        Schema::dropIfExists('onboarding_templates');
    }
};