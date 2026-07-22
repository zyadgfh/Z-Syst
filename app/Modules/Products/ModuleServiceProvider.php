<?php

declare(strict_types=1);

namespace App\Modules\Products;

use Illuminate\Support\ServiceProvider;

/**
 * Products Module Service Provider
 *
 * Registers routes, migrations, and bindings for the Products module.
 * Auto-discovered by App\Shared\Providers\ModuleServiceProvider.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerBindings();
    }

    /**
     * Register module bindings in the service container.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(
            \App\Modules\Products\Application\Services\ProductService::class,
            function ($app) {
                return new \App\Modules\Products\Application\Services\ProductService(
                    stockBatchService: $app->make(\App\Services\StockBatchService::class)
                );
            }
        );
    }
}

