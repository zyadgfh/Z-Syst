<?php

namespace App\Console\Commands;

use App\Services\CacheInvalidationService;
use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheSmartClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:smart-clear 
                            {--business= : Clear cache for specific business ID}
                            {--tag= : Clear specific cache tag}
                            {--model= : Clear cache for specific model (Sale, Product, etc.)}
                            {--all : Clear all cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Smartly clear application cache with selective invalidation';

    protected CacheService $cacheService;
    protected CacheInvalidationService $cacheInvalidationService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService, CacheInvalidationService $cacheInvalidationService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->cacheInvalidationService = $cacheInvalidationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Smart Cache Clear');
        $this->newLine();

        try {
            if ($this->option('all')) {
                $this->warn('⚠️  This will clear ALL cache. Are you sure?');
                if ($this->confirm('Do you want to continue?')) {
                    $this->cacheService->clearAll();
                    $this->info('✅ All cache cleared successfully');
                } else {
                    $this->info('❌ Operation cancelled');
                    return Command::SUCCESS;
                }
            } elseif ($this->option('business')) {
                $businessId = (int) $this->option('business');
                $this->info("Clearing cache for business ID: {$businessId}");
                $this->cacheInvalidationService->invalidateAllBusinessCache($businessId);
                $this->info("✅ Business cache cleared successfully");
            } elseif ($this->option('tag')) {
                $tag = $this->option('tag');
                $this->info("Clearing cache for tag: {$tag}");
                $this->cacheService->invalidateTags([$tag]);
                $this->info("✅ Tag cache cleared successfully");
            } elseif ($this->option('model')) {
                $model = $this->option('model');
                $this->info("Clearing cache for model: {$model}");
                $this->clearModelCache($model);
                $this->info("✅ Model cache cleared successfully");
            } else {
                $this->info("Clearing application cache...");
                $this->cacheService->clearAll();
                $this->info("✅ Application cache cleared successfully");
            }

            $this->newLine();
            $this->info('🎉 Cache clear completed!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Cache clear failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear cache for specific model
     */
    protected function clearModelCache(string $model): void
    {
        $modelMap = [
            'Sale' => ['sales', 'dashboard', 'statistics'],
            'Purchase' => ['purchases', 'dashboard', 'statistics'],
            'Product' => ['products', 'inventory', 'dashboard'],
            'Stock' => ['stock', 'inventory', 'dashboard'],
            'Party' => ['parties', 'customers', 'dashboard'],
            'User' => ['users', 'permissions'],
            'Setting' => ['settings'],
            'Plan' => ['plans'],
            'Category' => ['categories'],
            'Warehouse' => ['warehouses'],
        ];

        $tags = $modelMap[$model] ?? [$model];
        
        foreach ($tags as $tag) {
            $this->cacheService->invalidateTags([$tag]);
            $this->line("  - Cleared tag: {$tag}");
        }
    }
}
