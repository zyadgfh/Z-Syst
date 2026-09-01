<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix parties.phone: drop global unique, add per-business unique
        Schema::table('parties', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->unique(['business_id', 'phone'], 'unique_party_phone_per_business');
        });

        // Fix users.email: drop global unique, add per-business unique
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['business_id', 'email'], 'unique_user_email_per_business');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropUnique('unique_party_phone_per_business');
            $table->unique('phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('unique_user_email_per_business');
            $table->unique('email');
        });
    }
};
