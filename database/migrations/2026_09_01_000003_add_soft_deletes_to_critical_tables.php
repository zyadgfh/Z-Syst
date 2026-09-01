<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to expenses (financial records should be recoverable)
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'deleted_at')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add soft deletes to sale_returns (return records should be recoverable)
        if (Schema::hasTable('sale_returns') && !Schema::hasColumn('sale_returns', 'deleted_at')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add timestamps to sale_return_details (missing created_at/updated_at)
        if (Schema::hasTable('sale_return_details') && !Schema::hasColumn('sale_return_details', 'created_at')) {
            Schema::table('sale_return_details', function (Blueprint $table) {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
