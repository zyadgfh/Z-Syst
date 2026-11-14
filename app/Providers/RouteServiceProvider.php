<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(2000)->by($request->user()?->id ?: $request->ip());
        });

        // Guest authentication endpoints on the web side.
        //
        // POST /login is deliberately NOT in here: LoginRequest::authenticate()
        // already calls ensureIsNotRateLimited(), which allows 5 attempts keyed
        // on email|ip, hits on every failure and clears on success. That is
        // tighter and better keyed than a route-level bucket, and adding a second
        // counter over it would only make the two disagree.
        //
        // The reason these five need limiting is not password guessing — it is
        // that each one does real work per request:
        //
        //   forgot-password / reset-password  call Supabase GoTrue outbound. An
        //       unthrottled guest can burn the project's auth quota (a cheap DoS
        //       on the reset path for every legitimate user) and trigger
        //       unlimited recovery e-mails to any address, which is harassment.
        //   register  is a 403 in production, but under
        //       supabase.fallback_eloquent (dev/CI) it provisions an account and
        //       runs bcrypt — deliberately expensive, so unthrottled it is a CPU
        //       amplifier.
        //   otp-resend / otp-submit  are 410 stubs today; limited anyway so the
        //       throttle is already in place if they are ever implemented.
        //
        // Two buckets, because one is not enough: keyed on email|ip so an
        // attacker rotating addresses still pays per address, and keyed on ip
        // alone so rotating addresses from one host has a ceiling too.
        RateLimiter::for('auth-guest', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(20)->by((string) $request->ip()),
            ];
        });

        // reset-password carries a GoTrue recovery token rather than an e-mail
        // address, so there is nothing to key on but the client. The token is
        // high entropy and the real check is GoTrue's; this is quota protection.
        RateLimiter::for('auth-reset', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        });
    }
}
