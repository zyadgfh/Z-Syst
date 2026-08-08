<?php

$db = new PDO('sqlite:D:/Zyad/laragon/www/database/database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
echo "\n";
try {
    $count = $db->query('SELECT COUNT(*) FROM options')->fetchColumn();
    echo "options count: $count\n";
    $rows = $db->query("SELECT id, key, value FROM options WHERE key IN ('general','manage-pages','term-condition','privacy-policy') ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "--- {$row['key']} ---\n";
        echo substr($row['value'], 0, 400)."\n";
    }
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}
