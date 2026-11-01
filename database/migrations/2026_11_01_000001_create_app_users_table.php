<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local replica of public.app_users (Supabase Postgres).
 *
 * In production this table lives in the Supabase project and its primary key
 * references auth.users(id) — that foreign key is intentionally NOT recreated
 * here because auth.users does not exist outside Supabase. This migration only
 * provides the schema so `migrate:fresh`, seeders and the test suite can run
 * against sqlite (CI) or a self-hosted Postgres (see PROJECT_KB.md §5b).
 *
 * Column set mirrors app/Models/AppUser.php (auto-generated from the live
 * Supabase schema) and database/factories/UserFactory.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            // Mirrors auth.users.id (uuid) in Supabase — no default generator:
            // in production GoTrue supplies the id, in tests UserFactory sets it.
            $table->uuid('id')->primary();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('role')->default('staff')->index();
            $table->string('phone')->nullable();
            $table->string('image')->nullable();
            $table->string('lang')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('visibility')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
