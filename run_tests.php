<?php
// Run composer install first
$composer = shell_exec('composer install --no-interaction --prefer-dist 2>&1');
echo "Composer output:\n" . $composer . "\n";

// Then run tests
$test = shell_exec('php artisan test 2>&1');
echo "Test output:\n" . $test . "\n";
