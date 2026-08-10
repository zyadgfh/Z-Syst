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
        Schema::create('maintenance_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('title')->default('System Maintenance');
            $table->text('message')->nullable();
            $table->string('estimated_completion')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->json('allowed_users')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};