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
        // Workflow definitions table
        if (!Schema::hasTable('workflow_definitions')) {
            Schema::create('workflow_definitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('entity_type'); // purchase_order, sale, prescription, etc.
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('rules')->nullable(); // Additional workflow rules
                $table->timestamps();
                
                $table->index(['business_id', 'entity_type']);
                $table->index('code');
            });
        }

        // Workflow steps table
        if (!Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->integer('sequence')->default(0);
                $table->enum('approval_type', ['single', 'any', 'all', 'majority'])->default('single');
                $table->json('conditions')->nullable(); // Conditions to reach this step
                $table->json('actions')->nullable(); // Actions to perform after approval
                $table->boolean('is_final')->default(false);
                $table->timestamps();
                
                $table->index(['workflow_definition_id', 'sequence']);
            });
        }

        // Workflow step approvers table
        if (!Schema::hasTable('workflow_step_approvers')) {
            Schema::create('workflow_step_approvers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
                $table->string('approval_level')->default('required'); // required, optional, informational
                $table->integer('sequence')->default(0);
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                
                $table->index(['workflow_step_id', 'user_id']);
                $table->index(['workflow_step_id', 'role_id']);
            });
        }

        // Workflow instances table
        if (!Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
                $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('initiated_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['business_id', 'entity_type', 'entity_id']);
                $table->index('status');
                $table->index('initiated_at');
            });
        }

        // Workflow approvals table
        if (!Schema::hasTable('workflow_approvals')) {
            Schema::create('workflow_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
                $table->text('comments')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['workflow_instance_id', 'workflow_step_id']);
                $table->index(['user_id', 'status']);
                $table->index('status');
            });
        }

        // Workflow history table
        if (!Schema::hasTable('workflow_history')) {
            Schema::create('workflow_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action'); // initiated, step_changed, approved, rejected, cancelled
                $table->text('description')->nullable();
                $table->json('changes')->nullable(); // Before/after state
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                
                $table->index(['workflow_instance_id', 'created_at']);
                $table->index('action');
            });
        }

        // Workflow delegation table
        if (!Schema::hasTable('workflow_delegations')) {
            Schema::create('workflow_delegations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->text('reason')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['business_id', 'from_user_id']);
                $table->index(['to_user_id', 'is_active']);
                $table->index(['start_date', 'end_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_delegations');
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_step_approvers');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};