<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detect existing index names (SQLite)
        $existingIndexNames = [];
        try {
            $indexes = \DB::select("PRAGMA index_list('users')");
            foreach ($indexes as $idx) {
                $existingIndexNames[] = $idx->name ?? null;
            }
        } catch (\Exception $e) {
            // ignore if pragma not available
        }

        Schema::table('users', function (Blueprint $table) use ($existingIndexNames) {
            // Add missing fields if they don't exist
            if (! Schema::hasColumn('users', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('company_id');
                $table->foreign('business_id')->references('id')->on('companies')->onDelete('set null');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'image')) {
                $table->string('image')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'lang')) {
                $table->string('lang')->default('ar')->after('image');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(true)->after('lang');
            }

            if (! Schema::hasColumn('users', 'visibility')) {
                $table->json('visibility')->nullable()->after('status');
            }

            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('visibility');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }

            // Add 2FA fields
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable()->after('department_id');
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }

            // Add indexes (guarded to avoid duplicate index errors)
            if (! in_array('users_business_id_index', $existingIndexNames, true)) {
                try { $table->index('business_id'); } catch (\Exception $e) {}
            }

            if (! in_array('users_branch_id_index', $existingIndexNames, true)) {
                try { $table->index('branch_id'); } catch (\Exception $e) {}
            }

            if (! in_array('users_department_id_index', $existingIndexNames, true)) {
                try { $table->index('department_id'); } catch (\Exception $e) {}
            }

            if (! in_array('users_status_index', $existingIndexNames, true)) {
                try { $table->index('status'); } catch (\Exception $e) {}
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);

            $table->dropColumn([
                'business_id',
                'phone',
                'image',
                'lang',
                'status',
                'visibility',
                'branch_id',
                'department_id',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
