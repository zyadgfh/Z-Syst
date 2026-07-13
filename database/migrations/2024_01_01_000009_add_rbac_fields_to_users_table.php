<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable()->after('name');
                    $table->index('username');
                }

                if (! Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }

                if (! Schema::hasColumn('users', 'profile_photo')) {
                    $table->string('profile_photo')->nullable()->after('phone');
                }

                if (! Schema::hasColumn('users', 'status')) {
                    $table->string('status')->default('active')->after('profile_photo'); // active, inactive, suspended
                    $table->index('status');
                }

                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('status');
                }

                if (! Schema::hasColumn('users', 'last_activity_at')) {
                    $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
                }

                if (! Schema::hasColumn('users', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable()->after('last_activity_at');
                }

                if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
                }

                if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                }

                if (! Schema::hasColumn('users', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('two_factor_confirmed_at');
                    $table->index('branch_id');
                    if (Schema::hasTable('branches')) {
                        $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                    }
                }

                if (! Schema::hasColumn('users', 'department_id')) {
                    $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
                    $table->index('department_id');
                    if (Schema::hasTable('departments')) {
                        $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                    }
                }

                if (! Schema::hasColumn('users', 'job_title')) {
                    $table->string('job_title')->nullable()->after('department_id');
                }

                if (! Schema::hasColumn('users', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('job_title');
                    if (Schema::hasTable('users')) {
                        $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                    }
                }

                if (! Schema::hasColumn('users', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                    if (Schema::hasTable('users')) {
                        $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'username',
                'phone',
                'profile_photo',
                'status',
                'last_login_at',
                'last_activity_at',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'branch_id',
                'department_id',
                'job_title',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
