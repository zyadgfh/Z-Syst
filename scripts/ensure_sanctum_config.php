<?php

$source = __DIR__ . '/../config/sanctum.php';
$targetDir = __DIR__ . '/../vendor/laravel/sanctum/config';
$target = $targetDir . '/sanctum.php';

if (is_file($source) && is_dir(__DIR__ . '/../vendor/laravel/sanctum')) {
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    if (!is_file($target) || file_get_contents($target) !== file_get_contents($source)) {
        copy($source, $target);
    }
}
