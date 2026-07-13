<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure companies table has core test columns
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (! Schema::hasColumn('companies', 'slug')) {
                    $table->string('slug')->nullable()->index();
                }

                if (! Schema::hasColumn('companies', 'max_branches')) {
                    $table->unsignedInteger('max_branches')->nullable();
                }

                if (! Schema::hasColumn('companies', 'is_unlimited_branches')) {
                    $table->boolean('is_unlimited_branches')->default(false);
                }

                if (! Schema::hasColumn('companies', 'default_branch_limit')) {
                    $table->unsignedInteger('default_branch_limit')->nullable();
                }

                if (! Schema::hasColumn('companies', 'branch_limit_updated_at')) {
                    $table->timestamp('branch_limit_updated_at')->nullable();
                }

                if (! Schema::hasColumn('companies', 'branch_limit_updated_by')) {
                    if (Schema::hasTable('users')) {
                        $table->foreignId('branch_limit_updated_by')->nullable()->constrained('users')->nullOnDelete();
                    } else {
                        $table->unsignedBigInteger('branch_limit_updated_by')->nullable();
                    }
                }
            });
        }

        // Ensure permissions table has guard_name
        if (Schema::hasTable('permissions') && ! Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web');
            });
        }

        // Ensure roles table has guard_name
        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web');
            });
        }

        // Ensure drugs table exists for tests
        if (! Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table) {
                $table->id();
                if (Schema::hasTable('companies')) {
                    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                } else {
                    $table->unsignedBigInteger('company_id')->nullable();
                }
                $table->uuid('uuid')->nullable();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->string('barcode')->nullable()->unique();
                $table->string('manufacturer')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Do not drop tables in down for safety in this test helper migration
    }
};
