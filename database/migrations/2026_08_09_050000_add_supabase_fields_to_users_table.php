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
        Schema::table('users', function (Blueprint $table) {
            $table->string('supabase_id')->nullable()->after('id')->unique();
            $table->string('supabase_access_token')->nullable()->after('remember_token');
            $table->string('supabase_refresh_token')->nullable()->after('supabase_access_token');
            $table->timestamp('supabase_token_expires_at')->nullable()->after('supabase_refresh_token');
            
            $table->index('supabase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['supabase_id']);
            $table->dropColumn([
                'supabase_id',
                'supabase_access_token',
                'supabase_refresh_token',
                'supabase_token_expires_at',
            ]);
        });
    }
};