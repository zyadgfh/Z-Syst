<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'slug')) {
                $table->string('slug')->unique()->nullable()->index();
            }

            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('roles', 'color_badge')) {
                $table->string('color_badge')->default('#3B82F6');
            }

            if (! Schema::hasColumn('roles', 'priority')) {
                $table->unsignedInteger('priority')->default(0);
            }

            if (! Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false);
            }

            if (! Schema::hasColumn('roles', 'status')) {
                $table->boolean('status')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('roles', 'color_badge')) {
                $table->dropColumn('color_badge');
            }
            if (Schema::hasColumn('roles', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('roles', 'is_system')) {
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('roles', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
