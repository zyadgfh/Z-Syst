<?php

namespace App\Providers;

use App\Services\CacheWarmingService;
use App\Services\TenantResolver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class, function () {
            return new TenantResolver;
        });

        // Register CacheWarmingService as singleton
        $this->app->singleton(CacheWarmingService::class, function ($app) {
            return new CacheWarmingService($app->make(\App\Services\CacheService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Schema::defaultStringLength(191);
        
        // Warm up cache on application boot
        $this->warmupCache();
    }

    /**
     * Warm up application cache
     */
    protected function warmupCache(): void
    {
        try {
            // Only warm up cache if cache driver is available
            if (config('cache.default')) {
                $cacheWarmingService = app(CacheWarmingService::class);
                $cacheWarmingService->warmupApplication();
            }
        } catch (\Exception $e) {
            // Don't let cache warming failure affect application boot
            \Illuminate\Support\Facades\Log::warning('Cache warming skipped during boot: ' . $e->getMessage());
        }
    }
}
