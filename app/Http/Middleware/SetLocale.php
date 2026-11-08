<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The install gate guards the admin area, not the public storefront —
        // '/' must render (the landing page is what an uninstalled tenant sees).
        if (! app()->environment('testing') && request()->is('login')) {
            if (! file_exists(storage_path('installed'))) {
                return redirect('install');
            }
        }

        if ($request->has('lang')) {
            session(['lang' => $request->lang]);
        }

        $lang = session('lang') ?? 'en';

        app()->setLocale($lang);

        return $next($request);
    }
}
