<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduces the production `job_batches` table, which existed in Supabase but had no
 * migration in this repository (so `migrate:fresh` could not reproduce production).
 * Derived from the live database's information_schema / pg_catalog on 2026-11-10.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_batches')) {
            return;
        }

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total');
            $table->integer('pending');
            $table->integer('failed');
            $table->integer('created_at');
            $table->integer('cancelled_at')->nullable();
            $table->integer('finished_at')->nullable();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
    }
}
