<?php
// Simple SQLite to MySQL export without full Laravel bootstrap
$sqliteFile = __DIR__ . '/database.sqlite';
if (!file_exists($sqliteFile)) {
    die("SQLite database not found at: $sqliteFile\n");
}

try {
    $pdo = new PDO('sqlite:' . $sqliteFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $sql = "-- Z-Syst Database Export for MySQL\n";
    $sql .= "CREATE DATABASE IF NOT EXISTS `if0_41922398_pharmasy_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $sql .= "USE `if0_41922398_pharmasy_db`;\n\n";
    
    foreach ($tables as $tableName) {
        $sql .= "-- Table structure for `$tableName`\n";
        $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
        
        // Get CREATE TABLE statement
        $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='" . str_replace("'", "''", $tableName) . "'");
        $createSql = $stmt->fetchColumn();
        
        if ($createSql) {
            // Convert SQLite to MySQL
            $createSql = preg_replace('/INTEGER PRIMARY KEY AUTOINCREMENT/i', 'INT AUTO_INCREMENT PRIMARY KEY', $createSql);
            $createSql = preg_replace('/TEXT/i', 'LONGTEXT', $createSql);
            $createSql = preg_replace('/BOOLEAN/i', 'TINYINT(1)', $createSql);
            $createSql = preg_replace('/DATETIME/i', 'DATETIME', $createSql);
            $createSql = preg_replace('/DATE/i', 'DATE', $createSql);
            $createSql = preg_replace('/TIME/i', 'TIME', $createSql);
            $createSql = preg_replace('/REAL/i', 'DOUBLE', $createSql);
            $createSql = preg_replace('/BLOB/i', 'LONGBLOB', $createSql);
            
            // Replace double quotes with backticks for MySQL identifiers
            $createSql = preg_replace('/"([^"]+)"/', '`$1`', $createSql);
            
            // Fix NOT NULL spacing
            $createSql = preg_replace('/\bnot\s+null\b/i', 'NOT NULL', $createSql);
            
            // Add length to varchar if missing
            $createSql = preg_replace('/varchar\s*(\(\d+\))?\s*not\s+null/i', 'VARCHAR(255) NOT NULL', $createSql);
            $createSql = preg_replace('/varchar\s*(\(\d+\))?\s*$/im', 'VARCHAR(255)', $createSql);
            
            // Fix foreign key syntax for MySQL
            $createSql = preg_replace('/foreign\s+key\s*\(([^)]+)\)\s*references\s*`?([^`]+)`?\s*\(([^)]+)\)\s*on\s+delete\s+cascade/i', 'FOREIGN KEY ($1) REFERENCES `$2`($3) ON DELETE CASCADE', $createSql);
            
            // Remove SQLite CHECK constraints - MySQL doesn't support them well
            $createSql = preg_replace('/\s*check\s*\([^)]+\)/i', '', $createSql);
            
            $sql .= $createSql . ";\n\n";
        }
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $values = array_map(function($value) {
                if ($value === null) return 'NULL';
                return "'" . str_replace("'", "''", (string)$value) . "'";
            }, array_values($row));
            
            $sql .= "INSERT INTO `$tableName` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sql .= "\n";
    }
    
    file_put_contents(__DIR__ . '/database_export.sql', $sql);
    echo "Export completed successfully: database_export.sql\n";
    echo "Total tables exported: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}