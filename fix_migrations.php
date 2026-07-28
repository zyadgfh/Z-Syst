<?php
/**
 * Fix duplicate 2026_07_18_* migrations by adding hasTable guards.
 * Run: php fix_migrations.php
 */

$dir = __DIR__ . '/database/migrations';
$files = glob($dir . '/2026_07_18_*.php');

$fixed = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);

    // Skip files that already have hasTable check
    if (str_contains($content, 'hasTable')) {
        continue;
    }

    // Find the table name being created
    if (preg_match("/Schema::create\('([^']+)'/", $content, $matches)) {
        $tableName = $matches[1];

        // Add guard after the opening brace of up()
        $search = "public function up(): void\n    {";
        $replace = "public function up(): void\n    {\n        if (Schema::hasTable('{$tableName}')) {\n            return;\n        }";

        $newContent = str_replace($search, $replace, $content);

        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Fixed: {$tableName} (" . basename($file) . ")\n";
            $fixed++;
        } else {
            echo "WARNING: Could not fix " . basename($file) . " - pattern not found\n";
        }
    }
}

echo "\nFixed {$fixed} migration files.\n";

