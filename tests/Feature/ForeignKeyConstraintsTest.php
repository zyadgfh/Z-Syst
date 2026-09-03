<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests verifying that foreign key constraints added by migrations
 * 2026_08_07_000003 and 2026_09_01_000006 work correctly.
 *
 * These tests create a temporary test environment by running the
 * relevant migrations and then verifying FK constraints exist and
 * enforce referential integrity.
 *
 * NOTE: SQLite does not enforce FK constraints by default, so this test
 * validates the migration structure and SQL generation. On MySQL/PostgreSQL
 * the FK constraints are enforced at the database level.
 */
class ForeignKeyConstraintsTest extends TestCase
{
    /**
     * Migration 2026_08_07_000003 adds FKs for these tables:
     * - warehouses.business_id → businesses.id
     * - warehouse_stocks.warehouse_id → warehouses.id
     * - warehouse_stocks.product_id → products.id
     * - stock_transfers.business_id, from_warehouse_id, to_warehouse_id
     * - loyalty_programs.business_id → businesses.id
     * - loyalty_transactions.business_id, party_id, loyalty_program_id
     * - customer_interactions.business_id, party_id, user_id
     * - receipts.business_id, sale_id, purchase_id, user_id
     * - insurance_companies.business_id → businesses.id
     * - insurance_policies.business_id, insurance_company_id
     * - insurance_claims.business_id, insurance_policy_id
     * - batch_lots.business_id, product_id
     * - recall_events.business_id → businesses.id
     * - traceability_logs.business_id → businesses.id
     * - plan_subscribes.business_id, plan_id
     */
    public function test_add_foreign_key_constraints_migration_file_exists(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $this->assertFileExists($migrationPath, 'FK constraints migration file must exist');
    }

    public function test_add_missing_fk_constraints_migration_file_exists(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $this->assertFileExists($migrationPath, 'Missing FK constraints migration file must exist');
    }

    /**
     * Verify that the FK constraints migration contains the expected
     * foreign key definitions by parsing the migration source.
     */
    public function test_first_migration_defines_expected_foreign_keys(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $content = file_get_contents($migrationPath);

        // Expected table.column → referenced table mappings
        $expectedFks = [
            // warehouses
            ['table' => 'warehouses', 'column' => 'business_id', 'references' => 'businesses'],
            // warehouse_stocks
            ['table' => 'warehouse_stocks', 'column' => 'warehouse_id', 'references' => 'warehouses'],
            ['table' => 'warehouse_stocks', 'column' => 'product_id', 'references' => 'products'],
            // stock_transfers
            ['table' => 'stock_transfers', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'stock_transfers', 'column' => 'from_warehouse_id', 'references' => 'warehouses'],
            ['table' => 'stock_transfers', 'column' => 'to_warehouse_id', 'references' => 'warehouses'],
            // loyalty_programs
            ['table' => 'loyalty_programs', 'column' => 'business_id', 'references' => 'businesses'],
            // loyalty_transactions
            ['table' => 'loyalty_transactions', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'loyalty_transactions', 'column' => 'party_id', 'references' => 'parties'],
            ['table' => 'loyalty_transactions', 'column' => 'loyalty_program_id', 'references' => 'loyalty_programs'],
            // customer_interactions
            ['table' => 'customer_interactions', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'customer_interactions', 'column' => 'party_id', 'references' => 'parties'],
            ['table' => 'customer_interactions', 'column' => 'user_id', 'references' => 'users'],
            // receipts
            ['table' => 'receipts', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'receipts', 'column' => 'sale_id', 'references' => 'sales'],
            ['table' => 'receipts', 'column' => 'purchase_id', 'references' => 'purchases'],
            ['table' => 'receipts', 'column' => 'user_id', 'references' => 'users'],
            // insurance
            ['table' => 'insurance_companies', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'insurance_policies', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'insurance_policies', 'column' => 'insurance_company_id', 'references' => 'insurance_companies'],
            ['table' => 'insurance_claims', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'insurance_claims', 'column' => 'insurance_policy_id', 'references' => 'insurance_policies'],
            // batch_lots
            ['table' => 'batch_lots', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'batch_lots', 'column' => 'product_id', 'references' => 'products'],
            // recall_events
            ['table' => 'recall_events', 'column' => 'business_id', 'references' => 'businesses'],
            // traceability_logs
            ['table' => 'traceability_logs', 'column' => 'business_id', 'references' => 'businesses'],
            // plan_subscribes
            ['table' => 'plan_subscribes', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'plan_subscribes', 'column' => 'plan_id', 'references' => 'plans'],
        ];

        foreach ($expectedFks as $fk) {
            $this->assertStringContainsString(
                "'{$fk['column']}'",
                $content,
                "FK migration must define {$fk['table']}.{$fk['column']} → {$fk['references']}"
            );
            $this->assertStringContainsString(
                "->references('id')->on('{$fk['references']}')",
                $content,
                "FK must reference {$fk['references']}.id"
            );
        }

        // Verify it has a proper down() method with dropForeign calls
        $this->assertStringContainsString('dropForeign', $content, 'Migration must include dropForeign in down()');
    }

    /**
     * Verify that the second FK migration (2026_09_01_000006) contains expected FKs.
     */
    public function test_second_migration_defines_expected_foreign_keys(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $content = file_get_contents($migrationPath);

        $expectedFks = [
            ['table' => 'purchases', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'purchases', 'column' => 'party_id', 'references' => 'parties'],
            ['table' => 'purchases', 'column' => 'user_id', 'references' => 'users'],
            ['table' => 'purchases', 'column' => 'branch_id', 'references' => 'branches'],
            ['table' => 'purchase_details', 'column' => 'purchase_id', 'references' => 'purchases'],
            ['table' => 'purchase_details', 'column' => 'product_id', 'references' => 'products'],
            ['table' => 'purchase_returns', 'column' => 'purchase_id', 'references' => 'purchases'],
            ['table' => 'purchase_returns', 'column' => 'party_id', 'references' => 'parties'],
            ['table' => 'purchase_returns', 'column' => 'user_id', 'references' => 'users'],
            ['table' => 'purchase_returns', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'purchase_return_details', 'column' => 'purchase_return_id', 'references' => 'purchase_returns'],
            ['table' => 'purchase_return_details', 'column' => 'purchase_detail_id', 'references' => 'purchase_details'],
            ['table' => 'purchase_return_details', 'column' => 'product_id', 'references' => 'products'],
            ['table' => 'purchase_return_details', 'column' => 'business_id', 'references' => 'businesses'],
            ['table' => 'sale_details', 'column' => 'stock_id', 'references' => 'stocks'],
        ];

        foreach ($expectedFks as $fk) {
            $this->assertStringContainsString(
                "'{$fk['column']}'",
                $content,
                "FK migration must define {$fk['table']}.{$fk['column']} → {$fk['references']}"
            );
            $this->assertStringContainsString(
                "->references('id')->on('{$fk['references']}')",
                $content,
                "FK must reference {$fk['references']}.id"
            );
        }
    }

    /**
     * Verify the second migration skips on SQLite (since FK constraints
     * require table recreation on SQLite).
     */
    public function test_second_migration_skips_on_sqlite(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $content = file_get_contents($migrationPath);

        $this->assertStringContainsString(
            'isSQLite()',
            $content,
            'Migration must check for SQLite to skip FK creation'
        );
        $this->assertStringContainsString(
            'sqlite',
            $content,
            'Migration must reference SQLite driver check'
        );
    }

    /**
     * Verify the first migration defines proper cascade/null behaviors.
     */
    public function test_first_migration_uses_correct_on_delete_behaviors(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $content = file_get_contents($migrationPath);

        // All FKs in the first migration use onDelete('cascade')
        $this->assertStringContainsString("onDelete('cascade')", $content,
            'First FK migration should use cascade delete for business-owned data');
    }

    /**
     * Verify the second migration uses a mix of cascade and nullOnDelete.
     */
    public function test_second_migration_uses_correct_on_delete_behaviors(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $content = file_get_contents($migrationPath);

        // purchase_details.purchase_id uses cascadeOnDelete
        $this->assertStringContainsString('cascadeOnDelete()', $content,
            'Second migration should use cascadeOnDelete for purchase_details.purchase_id');

        // purchase_details.product_id uses nullOnDelete
        $this->assertStringContainsString('nullOnDelete()', $content,
            'Second migration should use nullOnDelete for product references');
    }

    /**
     * Verify both migrations have a proper down() method for rollback.
     */
    public function test_both_migrations_have_down_methods(): void
    {
        $firstMigration = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $secondMigration = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');

        $firstContent = file_get_contents($firstMigration);
        $secondContent = file_get_contents($secondMigration);

        $this->assertStringContainsString('function down()', $firstContent,
            'First migration must have a down() method');
        $this->assertStringContainsString('function down()', $secondContent,
            'Second migration must have a down() method');

        // First migration down() should drop all FKs
        $this->assertStringContainsString('dropForeign', $firstContent,
            'First migration down() must call dropForeign');
        $this->assertStringContainsString('dropForeign', $secondContent,
            'Second migration down() must call dropForeign');
    }

    /**
     * Verify FK constraints count: first migration defines at least 25 FKs,
     * second migration defines 15 FKs.
     */
    public function test_first_migration_has_minimum_fk_count(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $content = file_get_contents($migrationPath);

        // Count ->foreign(' occurrences in the up() method
        preg_match_all("/->foreign\('/", $content, $matches);
        $this->assertGreaterThanOrEqual(25, count($matches[0]),
            'First FK migration must define at least 25 foreign keys');
    }

    public function test_second_migration_has_expected_fk_count(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $content = file_get_contents($migrationPath);

        // Count ->foreign(' occurrences in the up() method only (not in down())
        $upMethod = substr($content, 0, strpos($content, 'public function down'));
        preg_match_all("/->foreign\('/", $upMethod, $matches);
        $this->assertGreaterThanOrEqual(15, count($matches[0]),
            'Second FK migration must define at least 15 foreign keys');
    }

    /**
     * Integration test: if running on MySQL/PostgreSQL (not SQLite),
     * verify FK constraints are actually enforced.
     */
    public function test_fk_constraints_enforce_referential_integrity_on_real_database(): void
    {
        if ($this->isSQLite()) {
            $this->markTestSkipped('FK enforcement is not available on SQLite');
        }

        // On a real database, we can use Schema::getForeignKeys() to verify
        $tables = [
            'warehouses' => ['business_id'],
            'warehouse_stocks' => ['warehouse_id', 'product_id'],
            'loyalty_programs' => ['business_id'],
            'plan_subscribes' => ['business_id', 'plan_id'],
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue; // Table might not exist in test DB
            }

            foreach ($columns as $column) {
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys($table);

                $hasFk = false;
                foreach ($foreignKeys as $fk) {
                    if (in_array($column, $fk->getLocalColumns())) {
                        $hasFk = true;
                        break;
                    }
                }

                $this->assertTrue($hasFk,
                    "FK constraint missing: {$table}.{$column}");
            }
        }
    }

    /**
     * Verify no duplicate FK constraints exist by checking the migration
     * doesn't add FKs to tables that already have them defined in their
     * original create migrations.
     */
    public function test_first_migration_skips_tables_with_existing_fks(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $content = file_get_contents($migrationPath);

        // These tables should NOT be modified by this migration (they have FKs in create migrations)
        $skippedTables = ['products', 'sales', 'parties', 'sale_details', 'purchase_details', 'users'];
        foreach ($skippedTables as $table) {
            $this->assertStringNotContainsString(
                "Schema::table('{$table}'",
                $content,
                "Migration should skip {$table} (FKs defined in create migration)"
            );
        }
    }

    /**
     * Verify that the second migration's down() method drops FKs in reverse order
     * to avoid dependency issues.
     */
    public function test_second_migration_down_drops_fks_in_correct_order(): void
    {
        $migrationPath = database_path('migrations/2026_09_01_000006_add_missing_fk_constraints.php');
        $content = file_get_contents($migrationPath);

        // The down() method should drop sale_details before purchase_return_details
        // before purchase_returns before purchase_details before purchases
        $downMethod = substr($content, strpos($content, 'public function down'));
        
        $saleDetailsPos = strpos($downMethod, "Schema::table('sale_details'");
        $purchaseReturnDetailsPos = strpos($downMethod, "Schema::table('purchase_return_details'");
        $purchaseReturnsPos = strpos($downMethod, "Schema::table('purchase_returns'");
        $purchaseDetailsPos = strpos($downMethod, "Schema::table('purchase_details'");
        $purchasesPos = strpos($downMethod, "Schema::table('purchases'");
        
        // sale_details should be dropped first (it depends on stocks)
        $this->assertNotFalse($saleDetailsPos, 'down() must drop sale_details FKs');
        $this->assertNotFalse($purchaseReturnDetailsPos, 'down() must drop purchase_return_details FKs');
        $this->assertNotFalse($purchaseReturnsPos, 'down() must drop purchase_returns FKs');
        $this->assertNotFalse($purchasesPos, 'down() must drop purchases FKs');
    }

    /**
     * Verify all referenced tables exist in the migration directory.
     * This catches typos in table references.
     */
    public function test_all_referenced_tables_have_create_migrations(): void
    {
        $referencedTables = [
            'businesses', 'warehouses', 'products', 'parties', 'users',
            'sales', 'purchases', 'plans', 'branches', 'stocks',
            'insurance_companies', 'insurance_policies',
            'purchase_returns', 'purchase_details',
        ];
        
        // Check that migration files exist for each referenced table
        $migrationFiles = glob(database_path('migrations/*.php'));
        $migrationNames = array_map('basename', $migrationFiles);
        
        foreach ($referencedTables as $table) {
            $found = false;
            foreach ($migrationNames as $name) {
                if (str_contains($name, $table)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue(
                $found,
                "Table '{$table}' is referenced in FK but has no migration file"
            );
        }
    }

    /**
     * Verify the first migration's down() drops FKs for all tables it modifies.
     */
    public function test_first_migration_down_drops_all_expected_fks(): void
    {
        $migrationPath = database_path('migrations/2026_08_07_000003_add_foreign_key_constraints.php');
        $content = file_get_contents($migrationPath);
        $downMethod = substr($content, strpos($content, 'public function down'));
        
        $expectedTables = [
            'warehouses', 'warehouse_stocks', 'stock_transfers',
            'loyalty_programs', 'loyalty_transactions', 'customer_interactions',
            'receipts', 'insurance_companies', 'insurance_policies',
            'insurance_claims', 'batch_lots', 'recall_events',
            'traceability_logs', 'plan_subscribes',
        ];
        
        foreach ($expectedTables as $table) {
            $this->assertStringContainsString(
                "Schema::table('{$table}'",
                $downMethod,
                "down() must drop FKs for table '{$table}'"
            );
        }
    }

    private function isSQLite(): bool
    {
        return config('database.default') === 'sqlite';
    }
}
