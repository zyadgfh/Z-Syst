<?php
// This script runs composer install and then tests in a single PHP process
// to avoid vendor being wiped between separate shell commands.

echo "=== STEP 1: composer install ===\n";
$installOutput = [];
exec('composer install --no-interaction --prefer-dist 2>&1', $installOutput, $installCode);
echo implode("\n", $installOutput) . "\n";
echo "Exit code: $installCode\n\n";

if ($installCode !== 0) {
    echo "FATAL: composer install failed!\n";
    exit(1);
}

// Verify vendor exists
if (!file_exists('vendor/laravel/framework/src/Illuminate/Foundation/Application.php')) {
    echo "FATAL: vendor directory still broken after install!\n";
    exit(1);
}
echo "Vendor OK - laravel/framework found.\n\n";

// Clear caches
echo "=== STEP 2: Clear caches ===\n";
exec('php artisan config:clear 2>&1', $out1);
exec('php artisan cache:clear 2>&1', $out2);
exec('php artisan route:clear 2>&1', $out3);
exec('php artisan view:clear 2>&1', $out4);
echo "Caches cleared.\n\n";

// Step 3: Run tests and save FULL output to file
echo "=== STEP 3: Run tests ===\n";
$output = [];
$exitCode = 0;
$handle = popen('php artisan test 2>&1', 'r');
while (!feof($handle)) {
    $line = fgets($handle);
    $output[] = $line;
    echo $line;
}
pclose($handle);

// Also write full output to file
file_put_contents('test_results.txt', implode('', $output));
echo "\n=== Results saved to test_results.txt ===\n";
