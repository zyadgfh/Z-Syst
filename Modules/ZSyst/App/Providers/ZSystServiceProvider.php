<?php

namespace Modules\ZSyst\App\Providers;

use Illuminate\Support\ServiceProvider;

class ZSystServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ZSyst';
    protected string $moduleNameLower = 'zsyst';

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('Modules/' . $this->moduleName . '/Database/migrations'));
        $this->loadViewsFrom(base_path('Modules/' . $this->moduleName . '/resources/views'), $this->moduleNameLower);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
