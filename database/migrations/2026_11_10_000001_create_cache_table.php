<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `cache` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cache')) {
            return;
        }

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->integer('expiration');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
    }
}
