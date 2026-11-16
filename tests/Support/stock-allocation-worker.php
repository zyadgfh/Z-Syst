<?php

// Invoked only by StockAllocationConcurrencyTest, against a disposable test DB.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$script, $directory, $worker, $stockId, $userId] = $argv;
file_put_contents($directory.'/ready-'.$worker, 'ready');
$deadline = microtime(true) + 20;
while (! file_exists($directory.'/start')) {
    if (microtime(true) > $deadline) {
        fwrite(STDERR, "Start barrier timed out\n");
        exit(2);
    }
    usleep(10000);
}
try {
    $stock = App\Models\Stock::findOrFail((int) $stockId);
    app(App\Services\Stock\StockAllocationService::class)->allocate(
        $stock, 1, App\Models\Sale::class, (int) $worker, (int) $userId
    );
    echo 'allocated';
} catch (App\Exceptions\StockUnavailableException $e) {
    echo 'unavailable';
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}
