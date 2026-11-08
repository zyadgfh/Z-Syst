<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs') && ! Schema::hasColumn('audit_logs', 'business_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
                $table->index(['business_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'business_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('business_id');
            });
        }
    }
};
