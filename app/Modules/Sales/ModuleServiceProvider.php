<?php

declare(strict_types=1);

namespace App\Modules\Sales;

use Illuminate\Support\ServiceProvider;

/**
 * Sales Module Service Provider
 *
 * Registers routes, migrations, and bindings for the Sales module.
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
        // Sale Service with FEFO support
        $this->app->bind(
            \App\Modules\Sales\Application\Services\SaleService::class,
            function ($app) {
                return new \App\Modules\Sales\Application\Services\SaleService(
                    stockBatchService: $app->make(\App\Services\StockBatchService::class),
                    stockMovementService: $app->make(\App\Services\StockMovementService::class),
                    fefoStockService: $app->make(\App\Services\FefoStockService::class),
                );
            }
        );

        // Receipt Service
        $this->app->singleton(\App\Services\ReceiptService::class);

        // Sales Stats Service
        $this->app->singleton(\App\Services\SaleStatsService::class);

        // Sale Controller with all dependencies
        $this->app->bind(
            \App\Modules\Sales\Infrastructure\Controllers\SaleController::class,
            function ($app) {
                return new \App\Modules\Sales\Infrastructure\Controllers\SaleController(
                    saleService: $app->make(\App\Modules\Sales\Application\Services\SaleService::class),
                    receiptService: $app->make(\App\Services\ReceiptService::class),
                    statsService: $app->make(\App\Services\SaleStatsService::class),
                );
            }
        );
    }
}

