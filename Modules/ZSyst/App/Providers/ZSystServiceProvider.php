<?php

namespace Modules\ZSyst\App\Providers;

use Illuminate\Support\ServiceProvider;

class ZSystServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ZSyst';
    protected string $moduleNameLower = 'zsyst';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
