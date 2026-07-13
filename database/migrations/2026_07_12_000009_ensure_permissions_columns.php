<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'slug')) {
                $table->string('slug')->nullable()->index();
            }

            if (! Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'module')) {
                $table->string('module')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'group')) {
                $table->string('group')->nullable();
            }

            if (! Schema::hasColumn('permissions', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }

            if (! Schema::hasColumn('permissions', 'status')) {
                $table->boolean('status')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('permissions', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('permissions', 'module')) {
                $table->dropColumn('module');
            }
            if (Schema::hasColumn('permissions', 'group')) {
                $table->dropColumn('group');
            }
            if (Schema::hasColumn('permissions', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('permissions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
