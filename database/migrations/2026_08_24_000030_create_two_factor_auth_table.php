<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('secret', 255)->nullable()->comment('Encrypted TOTP secret');
            $table->string('recovery_codes', 500)->nullable()->comment('Encrypted JSON array of recovery codes');
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_auth');
    }
};
