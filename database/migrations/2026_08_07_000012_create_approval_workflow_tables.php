<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->integer('current_step')->default(1);
            $table->string('status')->default('pending');
            $table->foreignUuid('created_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();

            // Production has always had this column; this migration never created
            // it, so `migrate:fresh` built an approval_steps without a tenant key
            // while production had one — the two databases disagreed on a
            // security-relevant column. A step belongs to the same business as its
            // workflow, and both TenantScoped and the database's RLS policies key
            // off it. Declared NOT NULL with a foreign key to match its two
            // sibling tables in this file and the state production was aligned to
            // by `enforce_tenant_ownership_on_business_id`.
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();

            $table->integer('step_number');
            $table->foreignUuid('approver_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('approver_role')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps_config');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_templates');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
    }
};
