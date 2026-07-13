<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'max_branches')) {
                    $table->unsignedInteger('max_branches')->nullable()->after('id');
                }

                if (! Schema::hasColumn('companies', 'is_unlimited_branches')) {
                    $table->boolean('is_unlimited_branches')->default(false)->after('max_branches');
                }

                if (! Schema::hasColumn('companies', 'default_branch_limit')) {
                    $table->unsignedInteger('default_branch_limit')->nullable()->after('is_unlimited_branches');
                }

                if (! Schema::hasColumn('companies', 'branch_limit_updated_at')) {
                    $table->timestamp('branch_limit_updated_at')->nullable()->after('default_branch_limit');
                }

                if (! Schema::hasColumn('companies', 'branch_limit_updated_by')) {
                    if (Schema::hasTable('users')) {
                        $table->foreignId('branch_limit_updated_by')->nullable()->constrained('users')->nullOnDelete()->after('branch_limit_updated_at');
                    } else {
                        $table->unsignedBigInteger('branch_limit_updated_by')->nullable()->after('branch_limit_updated_at');
                    }
                }
            });
        }
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
