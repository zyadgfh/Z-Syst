<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('max_branches')->nullable()->after('id');
            $table->boolean('is_unlimited_branches')->default(false)->after('max_branches');
            $table->unsignedInteger('default_branch_limit')->nullable()->after('is_unlimited_branches');
            $table->timestamp('branch_limit_updated_at')->nullable()->after('default_branch_limit');
            $table->foreignId('branch_limit_updated_by')->nullable()->constrained('users')->nullOnDelete()->after('branch_limit_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['branch_limit_updated_by']);
            $table->dropColumn([
                'max_branches',
                'is_unlimited_branches',
                'default_branch_limit',
                'branch_limit_updated_at',
                'branch_limit_updated_by',
            ]);
        });
    }
};
