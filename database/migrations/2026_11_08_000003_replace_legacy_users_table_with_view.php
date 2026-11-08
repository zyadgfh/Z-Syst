<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy integer-keyed `users` table (Path A) is retired: principals live in
 * app_users (uuid, Supabase GoTrue mirror). Tests and older queries still
 * reference `users` by name — expose app_users through a same-named view so
 * reads keep working without a second identity store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');
        \Illuminate\Support\Facades\DB::statement('CREATE VIEW users AS SELECT * FROM app_users');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP VIEW IF EXISTS users');
    }
};
