<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addIndex = static function (string $tableName, array $columns): void {
            $indexName = $tableName . '_' . implode('_', $columns) . '_index';
            $existingIndexes = collect(Schema::getIndexes($tableName))
                ->pluck('name')
                ->all();

            if (in_array($indexName, $existingIndexes, true)) {
                return;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
                $table->index($columns, $indexName);
            });
        };

        $indexes = [
            'products' => [
                ['business_id', 'is_active'],
                ['expiry_date'],
                ['category_id'],
                ['created_at'],
            ],
            'sales' => [
                ['business_id', 'saleDate'],
                ['party_id'],
                ['invoiceNumber'],
                ['created_at'],
            ],
            'purchases' => [
                ['business_id', 'purchaseDate'],
                ['party_id'],
                ['invoiceNumber'],
                ['created_at'],
            ],
            'parties' => [
                ['business_id', 'type'],
                ['phone'],
                ['name'],
            ],
            'sale_details' => [
                ['sale_id'],
                ['product_id'],
                ['sale_id', 'product_id'],
            ],
            'purchase_details' => [
                ['purchase_id'],
                ['product_id'],
                ['purchase_id', 'product_id'],
            ],
            'loyalty_transactions' => [
                ['business_id', 'created_at'],
                ['party_id'],
                ['program_id'],
            ],
            'customer_interactions' => [
                ['business_id', 'created_at'],
                ['party_id'],
                ['user_id'],
            ],
            'receipts' => [
                ['business_id', 'created_at'],
                ['receipt_number'],
                ['sale_id'],
                ['purchase_id'],
            ],
            'warehouses' => [
                ['business_id', 'is_active'],
            ],
            'warehouse_stocks' => [
                ['warehouse_id', 'product_id'],
                ['product_id'],
            ],
            'stock_transfers' => [
                ['business_id', 'transfer_date'],
                ['from_warehouse_id'],
                ['to_warehouse_id'],
                ['status'],
            ],
            'users' => [
                ['business_id', 'status'],
                ['email'],
                ['phone'],
            ],
            'businesses' => [
                ['plan_subscribe_id'],
                ['status'],
                ['will_expire'],
            ],
        ];

        foreach ($indexes as $tableName => $tableIndexes) {
            foreach ($tableIndexes as $columns) {
                $addIndex($tableName, $columns);
            }
        }
    }

    public function down(): void
    {
        // These indexes are optional performance enhancements. Leaving existing
        // indexes intact during rollback is safer than dropping indexes created
        // by earlier migrations or by production-specific schema changes.
    }
};
