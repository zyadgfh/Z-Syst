<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Uses separate PHP processes/connections, not SQLite or an uncommitted fixture. */
class StockAllocationConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        // DatabaseMigrations runs during parent setup, so guard before it can drop tables.
        if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'zsyst_test'
            || ! in_array(getenv('DB_CONNECTION'), ['pgsql', 'mysql'], true)) {
            $this->markTestSkipped('Requires an explicitly configured disposable zsyst_test database.');
        }
        parent::setUp();
    }

    public function test_two_workers_cannot_allocate_the_last_unit_twice(): void
    {
        if (! in_array(config('database.default'), ['pgsql', 'mysql'], true)) {
            $this->markTestSkipped('Row locking requires PostgreSQL or MySQL.');
        }
        if (config('app.env') !== 'testing' || config('database.connections.'.config('database.default').'.database') !== 'zsyst_test') {
            $this->fail('Concurrency test requires the dedicated zsyst_test database.');
        }

        $business = Business::factory()->create();
        $product = Product::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $stock = Stock::factory()->create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'productStock' => 1,
        ]);
        $directory = sys_get_temp_dir().'/zsyst-race-'.bin2hex(random_bytes(12));
        mkdir($directory, 0700);
        $processes = [];
        try {
            foreach ([1, 2] as $worker) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/stock-allocation-worker.php'), $directory, (string) $worker, (string) $stock->id, (string) $user->id], base_path());
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 20;
            while (! (file_exists($directory.'/ready-1') && file_exists($directory.'/ready-2'))) {
                if (microtime(true) > $deadline) {
                    $this->fail('Workers failed to reach the start barrier: '.implode(' ', array_map(fn ($p) => $p->getErrorOutput(), $processes)));
                }
                usleep(10000);
            }
            file_put_contents($directory.'/start', 'go');
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            }
            $results = array_map(fn ($p) => trim($p->getOutput()), $processes);
            sort($results);
            $this->assertSame(['allocated', 'unavailable'], $results);
            $this->assertSame(0, (int) $stock->fresh()->productStock);
            $this->assertSame(1, StockMovement::where('stock_id', $stock->id)->where('movement_type', 'out')->count());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
