<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_definition_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type'); // system, organization, branch, role, user
            $table->unsignedBigInteger('scope_id')->nullable(); // business_id for org, branch_id for branch, role_id for role, user_id for user; null for system
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['setting_definition_id', 'scope_type', 'scope_id']);
            $table->index(['scope_type', 'scope_id']);
            $table->index(['setting_definition_id', 'scope_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_values');
    }
};
