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
        if (request()->is('/') || request()->is('login')) {
            if (! file_exists(storage_path('installed'))) {
                return redirect('install');
            }
        }

        if ($request->has('lang')) {
            session(['lang' => $request->lang]);
        }

        $lang = session('lang') ?? 'ar';

        app()->setLocale($lang);

        return $next($request);
    }
}
