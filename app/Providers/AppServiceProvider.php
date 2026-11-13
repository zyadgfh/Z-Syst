<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ModelAuditObserver;
use App\Services\TenantResolver;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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

        // Every production URL this application generates must be https.
        //
        // The container sits behind Render's proxy, which terminates TLS and
        // forwards plain HTTP to nginx. Whether Laravel sees the request as
        // secure therefore depends entirely on X-Forwarded-Proto being trusted
        // (see App\Http\Middleware\TrustProxies, which trusts private ranges
        // only — a client-supplied header must not be able to flip the scheme).
        // Forcing the scheme removes that dependency for generated URLs: no
        // http:// redirect, no http:// asset, no http:// callback handed to a
        // payment gateway. HSTS in SecurityHeaders covers the browser side.
        //
        // Guarded on production so the test suite (APP_ENV=testing, sqlite
        // in-memory) keeps building plain http URLs against APP_URL.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Product::observe(ModelAuditObserver::class);

        // Allow tests to mock auth through the helper form: auth()->shouldReceive(...)
        // falls through AuthManager to the guard, whose macro proxies the Auth
        // facade swap.
        if (method_exists(SessionGuard::class, 'macro')) {
            SessionGuard::macro('shouldReceive', function (string $method) {
                return Auth::shouldReceive($method);
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
