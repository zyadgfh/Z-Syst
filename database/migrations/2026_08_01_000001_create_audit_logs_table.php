<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['business_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Production stores this as inet, which validates addresses and sorts them
        // correctly. Blueprint has no inet type, so on Postgres the column is narrowed
        // after creation; sqlite/CI keeps the varchar the builder produced.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE audit_logs ALTER COLUMN ip_address TYPE inet USING NULLIF(ip_address, '')::inet");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
