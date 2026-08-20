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
        // Parties table indexes
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasIndex('parties', 'idx_parties_business_type')) {
                $table->index(['business_id', 'type'], 'idx_parties_business_type');
            }
            if (!Schema::hasIndex('parties', 'idx_parties_business_name')) {
                $table->index(['business_id', 'name'], 'idx_parties_business_name');
            }
        });

        // Sale Details table indexes
        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_sale')) {
                $table->index('sale_id', 'idx_sale_details_sale');
            }
            if (!Schema::hasIndex('sale_details', 'idx_sale_details_product')) {
                $table->index('product_id', 'idx_sale_details_product');
            }
        });

        // Purchase Details table indexes
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_purchase')) {
                $table->index('purchase_id', 'idx_purchase_details_purchase');
            }
            if (!Schema::hasIndex('purchase_details', 'idx_purchase_details_product')) {
                $table->index('product_id', 'idx_purchase_details_product');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Parties table indexes
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex('idx_parties_business_type');
            $table->dropIndex('idx_parties_business_name');
        });

        // Sale Details table indexes
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropIndex('idx_sale_details_sale');
            $table->dropIndex('idx_sale_details_product');
        });

        // Purchase Details table indexes
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_details_purchase');
            $table->dropIndex('idx_purchase_details_product');
        });
    }
};