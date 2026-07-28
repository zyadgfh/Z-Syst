<?php

namespace App\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

/**
 * Module Service Provider
 *
 * Auto-loads all modules in app/Modules directory.
 * Each module must have a ModuleServiceProvider in its root directory.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register all modules
     */
    public function register(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);
            $providerCandidates = [
                "App\\Modules\\{$moduleName}\\ModuleServiceProvider",
                "App\\Modules\\{$moduleName}\\{$moduleName}Module",
            ];

            foreach ($providerCandidates as $providerClass) {
                if (class_exists($providerClass)) {
                    $this->app->register($providerClass);
                    break;
                }
            }
        }
    }

    /**
     * Boot all modules
     */
    public function boot(): void
    {
        // Module booting logic
    }
}

