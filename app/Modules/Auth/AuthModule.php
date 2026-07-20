<?php

namespace App\Modules\Auth;

use Illuminate\Support\ServiceProvider;

class AuthModule extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/Config/auth.php' => config_path('auth.php'),
        ], 'auth-config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register bindings
        $this->registerBindings();
    }

    /**
     * Register module bindings in the service container.
     */
    protected function registerBindings(): void
    {
        // Repository bindings
        // $this->app->bind(
        //     \App\Modules\Auth\Domain\Contracts\UserRepositoryInterface::class,
        //     \App\Modules\Auth\Infrastructure\Repositories\EloquentUserRepository::class
        // );

        // Service bindings
        // $this->app->bind(
        //     \App\Modules\Auth\Application\Services\AuthServiceInterface::class,
        //     \App\Modules\Auth\Application\Services\AuthService::class
        // );
    }
}
