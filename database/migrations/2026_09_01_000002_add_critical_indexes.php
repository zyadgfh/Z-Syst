<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === HIGH-PRIORITY: business_id on core tables ===
        $coreTables = [
            'businesses',
            'purchases',
            'subscriptions',
            'audit_logs',
            'banners',
            'plans',
            'gateways',
            'campaigns',
            'subscription_logs',
            'supplier_performance',
        ];

        foreach ($coreTables as $table) {
            if (Schema::hasTable($table) && !Schema::hasIndex($table, "idx_{$table}_business_id")) {
                Schema::table($table, function (Blueprint $t) {
                    $t->index('business_id');
                });
            }
        }

        // === FK indexes for join-heavy tables ===
        $fkIndexes = [
            'coupon_usages'          => ['coupon_id', 'user_id'],
            'purchase_return_details' => ['purchase_return_id', 'product_id'],
            'sale_return_details'    => ['sale_return_id', 'sale_detail_id'],
            'loyalty_points'         => ['user_id'],
            'payment_schedules'      => ['supplier_id'],
            'credit_debit_items'     => ['parent_id'],
            'quality_checks'         => ['grn_item_id'],
            'quality_reports'        => ['business_id'],
            'grn_items'              => ['grn_id', 'product_id'],
            'loyalty_transactions'   => ['loyalty_program_id', 'party_id'],
        ];

        foreach ($fkIndexes as $table => $columns) {
            foreach ($columns as $col) {
                $idxName = "idx_{$table}_{$col}";
                if (Schema::hasTable($table) && !Schema::hasIndex($table, $idxName)) {
                    Schema::table($table, function (Blueprint $t) use ($col) {
                        $t->index($col);
                    });
                }
            }
        }

        // === Unique constraint on users.email ===
        if (Schema::hasTable('users') && !Schema::hasIndex('users', 'idx_users_email_unique')) {
            Schema::table('users', function (Blueprint $t) {
                $t->unique('email');
            });
        }
    }

    public function down(): void
    {
        $allTables = [
            'businesses', 'purchases', 'subscriptions', 'audit_logs',
            'banners', 'plans', 'gateways', 'campaigns', 'subscription_logs',
            'supplier_performance', 'coupon_usages', 'purchase_return_details',
            'sale_return_details', 'loyalty_points', 'payment_schedules',
            'credit_debit_items', 'quality_checks', 'quality_reports',
            'grn_items', 'loyalty_transactions',
        ];

        foreach ($allTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndexes(array_map(
                        fn($idx) => $idx->getName(),
                        Schema::getIndexes($t->getTable())
                    ));
                });
            }
        }

        Schema::table('users', function (Blueprint $t) {
            $t->dropIndex('idx_users_email_unique');
        });
    }
};
