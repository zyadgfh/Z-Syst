<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for SQLite
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Clear existing data
        $this->clearTables();

        // Enable foreign key checks for SQLite
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Run seeders
        $this->call([
            SubscriptionPlanSeeder::class,
            PlanSeeder::class,
            PermissionSeeder::class,
            ItemsPermissionsSeeder::class,
            UserSeeder::class,
            // Skip problematic seeders for now
            // BusinessSeeder::class,
            // BranchSeeder::class,
            // PaymentGatewaySeeder::class,
            // CurrencySeeder::class,
            // CategorySeeder::class,
            // ManufacturerSeeder::class,
            // UnitSeeder::class,
            // TypeTableSeeder::class,
            // TaxTableSeeder::class,
            // ProductSeeder::class,
            // PartySeeder::class,
            // OptionTableSeeder::class,
            // BoxSizeSeeder::class,
            // BusinessCategorySeeder::class,
            // LanguageSeeder::class,
            // AdvertiseSeeder::class,
            // DrugInteractionSeeder::class,
        ]);
    }

    protected function clearTables(): void
    {
        $tables = [
            'maintenance_settings',
            'audit_logs',
            'receipts',
            'receipt_settings',
            'loyalty_transactions',
            'customer_interactions',
            'loyalty_programs',
            'stock_transfers',
            'warehouse_stocks',
            'warehouses',
            'traceability_logs',
            'recall_events',
            'batch_lots',
            'insurance_claims',
            'insurance_policies',
            'insurance_companies',
            'sale_details',
            'sales',
            'purchase_details',
            'purchases',
            'stocks',
            'products',
            'parties',
            'plan_subscribes',
            'users',
            'businesses',
            'permissions',
            'roles',
            'plans',
            'branches',
            'company_payment_gateways',
            'payment_transactions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                if (config('database.default') === 'sqlite') {
                    DB::table($table)->delete();
                } else {
                    DB::table($table)->truncate();
                }
            }
        }
    }
}
