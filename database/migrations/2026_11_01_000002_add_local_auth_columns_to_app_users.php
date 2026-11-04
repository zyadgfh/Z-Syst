<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Local-auth columns for app_users (Supabase fallback mode).
 *
 * Under Path B the email lives in auth.users and passwords are never stored
 * locally. In local dev / CI (SUPABASE_FALLBACK_ELOQUENT=true) there is no
 * GoTrue, so these nullable columns carry the email and a bcrypt hash instead.
 * Production keeps them NULL and untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->string('email')->nullable()->index();
            $table->string('password')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['email', 'password']);
        });
    }
};
