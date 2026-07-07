<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('profile_photo')->nullable()->after('phone');
            $table->string('status')->default('active')->after('profile_photo'); // active, inactive, suspended
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
            $table->text('two_factor_secret')->nullable()->after('last_activity_at');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedBigInteger('branch_id')->nullable()->after('two_factor_confirmed_at');
            $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            $table->string('job_title')->nullable()->after('department_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('job_title');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->index('username');
            $table->index('status');
            $table->index('branch_id');
            $table->index('department_id');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
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
