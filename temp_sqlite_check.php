<?php
$db = 'database/testing.sqlite';
echo "DB FILE: $db\n";
if (!file_exists($db)) {
    echo "missing\n";
    exit(1);
}
$pdo = new PDO('sqlite:' . $db);
$stmt = $pdo->query('SELECT name FROM sqlite_master WHERE type="table" ORDER BY name');
if (!$stmt) {
    echo "query failed\n";
    exit(1);
}
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TABLE COUNT: " . count($rows) . "\n";
foreach ($rows as $row) {
    echo "$row\n";
}
?>
