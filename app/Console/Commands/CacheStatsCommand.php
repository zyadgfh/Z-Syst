<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Services\CacheWarmingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats 
                            {--test : Run a quick cache test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cache statistics and health information';

    protected CacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Cache Statistics');
        $this->newLine();

        try {
            // Cache driver info
            $driver = config('cache.default');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Cache Driver', $driver],
                    ['Cache Prefix', config('cache.prefix', 'laravel')],
                ]
            );

            // Cache stats
            $stats = $this->cacheService->getCacheStats();
            
            if (isset($stats['stats']) && is_array($stats['stats'])) {
                $this->info('📈 Redis Statistics:');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Hits', $stats['stats']['keyspace_hits'] ?? 'N/A'],
                        ['Misses', $stats['stats']['keyspace_misses'] ?? 'N/A'],
                        ['Hit Rate', $this->calculateHitRate($stats['stats'])],
                    ]
                );
            }

            // Test cache if requested
            if ($this->option('test')) {
                $this->newLine();
                $this->info('🧪 Running Cache Test...');
                $this->runCacheTest();
            }

            $this->newLine();
            $this->info('✅ Cache stats retrieved successfully');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to get cache stats: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(array $stats): string
    {
        $hits = $stats['keyspace_hits'] ?? 0;
        $misses = $stats['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return '0%';
        }

        $rate = ($hits / $total) * 100;
        return number_format($rate, 2) . '%';
    }

    /**
     * Run a quick cache test
     */
    protected function runCacheTest(): void
    {
        $testKey = 'cache_test_' . time();
        $testValue = 'Hello, Cache!';

        // Test write
        $writeStart = microtime(true);
        Cache::put($testKey, $testValue, 60);
        $writeTime = (microtime(true) - $writeStart) * 1000;

        // Test read
        $readStart = microtime(true);
        $readValue = Cache::get($testKey);
        $readTime = (microtime(true) - $readStart) * 1000;

        // Test delete
        $deleteStart = microtime(true);
        Cache::forget($testKey);
        $deleteTime = (microtime(true) - $deleteStart) * 1000;

        $this->table(
            ['Operation', 'Time (ms)', 'Status'],
            [
                ['Write', number_format($writeTime, 2), $readValue === $testValue ? '✅ Success' : '❌ Failed'],
                ['Read', number_format($readTime, 2), $readValue === $testValue ? '✅ Success' : '❌ Failed'],
                ['Delete', number_format($deleteTime, 2), '✅ Success'],
            ]
        );

        $this->newLine();
        $this->info("Write: " . number_format($writeTime, 2) . "ms");
        $this->info("Read: " . number_format($readTime, 2) . "ms");
        $this->info("Delete: " . number_format($deleteTime, 2) . "ms");
    }
}
