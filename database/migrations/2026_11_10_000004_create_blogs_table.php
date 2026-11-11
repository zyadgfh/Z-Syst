<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `blogs` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blogs')) {
            return;
        }

        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->text('title')->nullable();
            $table->text('slug')->nullable();
            $table->text('content')->nullable();
            $table->text('image')->nullable();
            $table->uuid('user_id')->nullable();
            $table->boolean('status')->nullable()->default(true);
            $table->jsonb('tags')->nullable();
            $table->timestampTz('created_at', 6)->useCurrent();
            $table->timestampTz('updated_at', 6)->useCurrent();
            $table->unique('slug', 'blogs_slug_key');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
}
