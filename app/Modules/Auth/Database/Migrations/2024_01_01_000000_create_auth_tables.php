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
        // User 2FA settings table
        Schema::create('user_2fa_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->string('method')->nullable()->comment('sms, email, authenticator');
            $table->text('backup_codes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'method']);
        });

        // OAuth tokens table (for 3rd party integrations)
        Schema::create('user_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider');
            $table->string('provider_user_id');
            $table->longText('access_token');
            $table->longText('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_2fa_settings');
        Schema::dropIfExists('user_oauth_tokens');
    }
};
