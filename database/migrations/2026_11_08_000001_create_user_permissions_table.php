<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runtime permission grants that extend the static role map in
     * config/supabase.php. user_id references public.app_users (uuid).
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 64);
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
