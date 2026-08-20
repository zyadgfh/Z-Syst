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
        Schema::table('sale_details', function (Blueprint $table) {
            // Add stock_id to track which stock batch was used
            if (!Schema::hasColumn('sale_details', 'stock_id')) {
                $table->unsignedBigInteger('stock_id')->nullable()->after('product_id');
            }

            // Add indexes for performance
            if (!Schema::hasIndex('sale_details', ['sale_id', 'stock_id'])) {
                $table->index(['sale_id', 'stock_id']);
            }

            if (!Schema::hasIndex('sale_details', ['product_id', 'stock_id'])) {
                $table->index(['product_id', 'stock_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex(['sale_id', 'stock_id']);
            $table->dropIndex(['product_id', 'stock_id']);
            $table->dropColumn('stock_id');
        });
    }
};
