<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SELECT name FROM sqlite_master WHERE type="table" AND name NOT LIKE "sqlite_%"');
$sql = "-- Z-Syst Database Export for MySQL\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `if0_41922398_pharmasy_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sql .= "USE `if0_41922398_pharmasy_db`;\n\n";

foreach ($tables as $table) {
    $tableName = $table->name;
    $sql .= "-- Table structure for `$tableName`\n";
    $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";

    $createTable = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
    if ($createTable && !empty($createTable[0]->sql)) {
        $createSql = $createTable[0]->sql;
        // Convert SQLite create table to MySQL
        $createSql = preg_replace('/INTEGER PRIMARY KEY AUTOINCREMENT/i', 'INT AUTO_INCREMENT PRIMARY KEY', $createSql);
        $createSql = preg_replace('/TEXT/i', 'LONGTEXT', $createSql);
        $createSql = preg_replace('/BOOLEAN/i', 'TINYINT(1)', $createSql);
        $createSql = preg_replace('/DATETIME/i', 'DATETIME', $createSql);
        $createSql = preg_replace('/DATE/i', 'DATE', $createSql);
        $createSql = preg_replace('/TIME/i', 'TIME', $createSql);
        $createSql = preg_replace('/REAL/i', 'DOUBLE', $createSql);
        $createSql = preg_replace('/BLOB/i', 'LONGBLOB', $createSql);
        // Replace double quotes with backticks for MySQL
        $createSql = preg_replace('/"([^"]+)"/', '`$1`', $createSql);
        // Fix NOT NULL spacing
        $createSql = preg_replace('/\bnot\s+null\b/i', 'NOT NULL', $createSql);
        // Add length to varchar if missing
        $createSql = preg_replace('/varchar\s*(\(\d+\))?\s*not\s+null/i', 'VARCHAR(255) NOT NULL', $createSql);
        $createSql = preg_replace('/varchar\s*(\(\d+\))?\s*$/im', 'VARCHAR(255)', $createSql);
        // Fix foreign key syntax for MySQL
        $createSql = preg_replace('/foreign\s+key\s*\(([^)]+)\)\s*references\s*`?([^`]+)`?\s*\(([^)]+)\)\s*on\s+delete\s+cascade/i', 'FOREIGN KEY ($1) REFERENCES `$2`($3) ON DELETE CASCADE', $createSql);
        // Remove SQLite-specific rowid references if any
        $sql .= $createSql . ";\n\n";
    }

    $rows = DB::table($tableName)->get();
    foreach ($rows as $row) {
        $columns = array_keys((array)$row);
        $values = array_map(function($value) {
            if ($value === null) return 'NULL';
            return "'" . str_replace("'", "''", (string)$value) . "'";
        }, array_values((array)$row));
        $sql .= "INSERT INTO `$tableName` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
    }
    $sql .= "\n";
}

file_put_contents(__DIR__ . '/database_export.sql', $sql);
echo "Export completed: database_export.sql\n";