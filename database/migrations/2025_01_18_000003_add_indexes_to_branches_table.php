<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Add missing indexes
            $table->index('company_id');
            $table->index('is_active');
            $table->index('is_main');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_main']);
        });
    }
};
