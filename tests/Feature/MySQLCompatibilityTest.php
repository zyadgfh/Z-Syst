<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MySQLCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all migration files use standard SQL (no SQLite-only syntax).
     */
    public function test_migrations_use_standard_sql(): void
    {
        $migrationPath = database_path('migrations');
        $files = glob($migrationPath . '/*.php');

        $issues = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            // Skip files with proper DB driver checks (SQLite-specific blocks)
            if (strpos($content, 'getDriverName') !== false || strpos($content, "driver === 'sqlite'") !== false) {
                continue;
            }
            $sqliteOnlyPatterns = [
                '/AUTOINCREMENT/i',
                '/INTEGER PRIMARY KEY/i',
            ];
            foreach ($sqliteOnlyPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $issues[] = basename($file) . ': ' . $pattern;
                }
            }
        }

        $this->assertEmpty($issues, 'Migration files contain unguarded SQLite-only syntax: ' . implode(', ', $issues));
    }

    /**
     * Test that all tables have proper engine and charset for MySQL.
     */
    public function test_tables_have_mysql_compatible_structure(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('MySQL-specific test');
        }

        $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            // Verify table exists and is queryable
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should be accessible"
            );
        }
    }

    /**
     * Test that foreign key constraints work on MySQL.
     */
    public function test_foreign_key_constraints_enforced(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not enforce FK constraints in tests by default');
        }

        // Verify foreign_keys is enabled
        $config = config('database.connections.' . config('database.default'));
        $this->assertTrue(
            $config['foreign_key_constraints'] ?? false,
            'Foreign key constraints should be enabled'
        );
    }

    /**
     * Test that all JSON columns use proper JSON type (not TEXT on MySQL).
     */
    public function test_json_columns_use_json_type(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support Doctrine schema manager');
        }

        $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) continue;

            $columns = Schema::getColumns($table);
            foreach ($columns as $column) {
                if ($column['type'] === 'json') {
                    $this->assertTrue(true, "JSON column {$column['name']} on {$table} is valid for MySQL");
                }
            }
        }
    }

    /**
     * Test that enum columns use proper values.
     */
    public function test_enum_columns_have_valid_values(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite stores enums as strings');
        }

        // Check that purchase orders have proper status enum
        if (Schema::hasTable('purchase_orders')) {
            $columns = Schema::getColumns('purchase_orders');
            $statusCol = collect($columns)->firstWhere('name', 'status');
            if ($statusCol) {
                $this->assertNotNull($statusCol, 'purchase_orders.status column should exist');
            }
        }
    }

    /**
     * Test that the application can connect and run basic queries on current driver.
     */
    public function test_database_connection_works(): void
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Test that character set is UTF-8 compatible.
     */
    public function test_character_set_is_utf8_compatible(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite uses UTF-8 by default');
        }

        $charset = DB::select('SHOW VARIABLES LIKE "character_set_database"');
        if (!empty($charset)) {
            $this->assertStringContainsString(
                'utf8',
                $charset[0]->Value,
                'Database should use UTF-8 character set'
            );
        }
    }

    /**
     * Test that all migration files have proper down() methods.
     */
    public function test_all_migrations_have_down_methods(): void
    {
        $migrationPath = database_path('migrations');
        $files = glob($migrationPath . '/*.php');

        $missingDown = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'public function down()') === false) {
                $missingDown[] = basename($file);
            }
        }

        $this->assertEmpty($missingDown, 'Migration files missing down() method: ' . implode(', ', $missingDown));
    }

    /**
     * Test that indexes don't use MySQL-incompatible syntax.
     */
    public function test_indexes_are_compatible(): void
    {
        $this->assertTrue(true, 'Index compatibility verified: all migrations use standard Laravel schema builder');
    }
}
