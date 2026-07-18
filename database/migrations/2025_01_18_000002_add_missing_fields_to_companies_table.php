<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add branch limit fields if they don't exist
            if (! Schema::hasColumn('companies', 'max_branches')) {
                $table->integer('max_branches')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('companies', 'is_unlimited_branches')) {
                $table->boolean('is_unlimited_branches')->default(false)->after('max_branches');
            }

            if (! Schema::hasColumn('companies', 'default_branch_limit')) {
                $table->integer('default_branch_limit')->nullable()->after('is_unlimited_branches');
            }

            if (! Schema::hasColumn('companies', 'branch_limit_updated_at')) {
                $table->timestamp('branch_limit_updated_at')->nullable()->after('default_branch_limit');
            }

            if (! Schema::hasColumn('companies', 'branch_limit_updated_by')) {
                $table->unsignedBigInteger('branch_limit_updated_by')->nullable()->after('branch_limit_updated_at');
                $table->foreign('branch_limit_updated_by')->references('id')->on('users')->onDelete('set null');
            }

            // Add indexes
            $table->index('max_branches');
            $table->index('is_unlimited_branches');
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
