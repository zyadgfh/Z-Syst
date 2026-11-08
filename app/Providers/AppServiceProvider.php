<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ModelAuditObserver;
use App\Services\TenantResolver;
use Illuminate\Support\Facades\Http;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Product::observe(ModelAuditObserver::class);

        // Allow tests to mock the auth manager through the helper form:
        // auth()->shouldReceive('user') proxies to the Auth facade swap.
        if (method_exists(\\Illuminate\\Auth\\AuthManager::class, 'macro')) {
            \\Illuminate\\Auth\\AuthManager::macro('shouldReceive', function (string $method) {
                return \\Illuminate\\Support\\Facades\\Auth::shouldReceive($method);
            });
        }

        // Defensive: cap every outbound HTTP request so a misbehaving
        // integration (ETA, Supabase storage, Telegram, ...) can never wedge
        // a request or a CI run. Individual services may override with a
        // stricter ->timeout().
        Http::globalMiddleware(function ($handler) {
            return function ($request, array $options) use ($handler) {
                return $handler($request, array_merge([
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ], $options));
            };
        });
    }
}
