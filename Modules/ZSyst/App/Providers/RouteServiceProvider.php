<?php

namespace Modules\ZSyst\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\\ZSyst\\App\\Http\\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/zsyst')
            ->middleware(['api'])
            ->namespace($this->moduleNamespace)
            ->group(module_path('ZSyst', '/routes/api.php'));

        Route::prefix('api/z-syst')
            ->middleware(['api'])
            ->namespace($this->moduleNamespace)
            ->group(module_path('ZSyst', '/routes/api.php'));
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web'])
            ->namespace($this->moduleNamespace)
            ->group(module_path('ZSyst', '/routes/web.php'));
    }
}
