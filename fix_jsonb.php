<?php
/**
 * Fix jsonb -> json for SQLite compatibility across all migrations.
 * Run: php fix_jsonb.php
 */

$dir = __DIR__ . '/database/migrations';
$files = glob($dir . '/2026_07_18_*.php');
$files = array_merge($files, glob($dir . '/2026_07_*.php'));

$fixed = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);

    if (!str_contains($content, '->jsonb(')) {
        continue;
    }

    $newContent = str_replace('->jsonb(', '->json(', $content);

    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Fixed jsonb: " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\nFixed {$fixed} files for jsonb -> json.\n";

