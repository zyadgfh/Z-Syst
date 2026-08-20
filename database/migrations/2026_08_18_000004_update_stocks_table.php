<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Add soft deletes if not exists
            if (!Schema::hasColumn('stocks', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add purchase_price and cost_price if they don't exist
            if (!Schema::hasColumn('stocks', 'purchase_price')) {
                $table->decimal('purchase_price', 10, 2)->nullable()->after('barcode');
            }

            if (!Schema::hasColumn('stocks', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->nullable()->after('purchase_price');
            }

            // Add indexes for price columns
            if (!Schema::hasIndex('stocks', 'purchase_price')) {
                $table->index('purchase_price');
            }

            if (!Schema::hasIndex('stocks', 'cost_price')) {
                $table->index('cost_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['purchase_price']);
            $table->dropIndex(['cost_price']);
            $table->dropColumn(['purchase_price', 'cost_price']);
        });
    }
};
