<?php

$pdo = new PDO('sqlite:D:/Zyad/laragon/www/database/database.sqlite');
$keys = ['general', 'manage-pages', 'term-condition', 'privacy-policy'];
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = $pdo->prepare("SELECT id, key, value FROM options WHERE key IN ($placeholders)");
$stmt->execute($keys);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$replacements = [
    ['Acnoo', 'Z-Syst'],
    ['acnoo', 'z-syst'],
    ['ACNOO', 'Z-SYST'],
    ['Pharmacy Store', 'Z-Syst Pharmacy'],
    ['Acnoo Pharmacy', 'Z-Syst Pharmacy'],
    ['Acnoo Team', 'Z-Syst Team'],
    ['Acnoo is', 'Z-Syst is'],
    ['acnoo.com', 'z-syst.com'],
    ['https://acnoo.com/', 'https://z-syst.com/'],
    ['https:\/\/acnoo.com\/', 'https:\/\/z-syst.com\/'],
    ['© 2025 Acnoo, all rights reserved.', '© 2025 Z-Syst, all rights reserved.'],
    ['acnooteam', 'zsyst'],
    ['instagram.com/acnooteam', 'instagram.com/zsyst'],
    ['facebook.com/acnooteam', 'facebook.com/zsyst'],
];

$updated = 0;
foreach ($rows as $row) {
    $value = $row['value'];
    foreach ($replacements as [$from, $to]) {
        $value = str_replace($from, $to, $value);
    }
    if ($value !== $row['value']) {
        $update = $pdo->prepare('UPDATE options SET value = ? WHERE id = ?');
        $update->execute([$value, $row['id']]);
        $updated++;
    }
}

echo "Updated rows: $updated\n";
