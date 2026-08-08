<?php

namespace App\Providers;

use App\Services\XSSProtectionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(XSSProtectionService::class, function ($app) {
            return new XSSProtectionService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Custom directive for safe HTML output
        Blade::directive('safe', function ($expression) {
            return "<?php echo e(\$__env->getContainer()->make(\App\Services\XSSProtectionService::class)->cleanUserInput($expression)); ?>";
        });

        // Custom directive for safe URL output
        Blade::directive('safeUrl', function ($expression) {
            return "<?php echo e(\$__env->getContainer()->make(\App\Services\XSSProtectionService::class)->sanitizeURL($expression)); ?>";
        });

        // Custom directive for safe attribute output
        Blade::directive('safeAttr', function ($expression) {
            return "<?php echo e(\$__env->getContainer()->make(\App\Services\XSSProtectionService::class)->safeAttribute($expression)); ?>";
        });

        // Custom directive for allowing specific HTML tags
        Blade::directive('stripExcept', function ($expression) {
            return "<?php echo e(\$__env->getContainer()->make(\App\Services\XSSProtectionService::class)->stripTagsExcept($expression)); ?>";
        });
    }
}
