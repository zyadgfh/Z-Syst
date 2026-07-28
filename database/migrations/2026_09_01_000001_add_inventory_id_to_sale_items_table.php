<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Add inventory_id for FEFO batch tracking (link to inventory table)
            $table->uuid('inventory_id')
                ->nullable()
                ->after('product_id')
                ->comment('معرف المخزون (ربط FEFO)');

            // Add cost_price for profit tracking per item
            $table->decimal('cost_price', 12, 3)
                ->default(0)
                ->after('unit_price')
                ->comment('سعر التكلفة');

            // Foreign key for inventory
            $table->foreign('inventory_id', 'fk_sale_items_inventory_id')
                ->references('id')->on('inventory')
                ->onDelete('set null');

            // Index for inventory lookups
            $table->index('inventory_id', 'idx_sale_items_inventory_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign('fk_sale_items_inventory_id');
            $table->dropIndex('idx_sale_items_inventory_id');
            $table->dropColumn(['inventory_id', 'cost_price']);
        });
    }
};

