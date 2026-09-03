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

        // If user requested a language change via query param
        if ($request->has('lang')) {
            $newLang = $request->lang;
            session(['lang' => $newLang]);

            // Persist to user's DB preference if authenticated
            if (auth()->check()) {
                auth()->user()->update(['lang' => $newLang]);
            }
        }

        // Resolution order: query param > session > user DB preference > default
        $lang = session('lang');

        if (! $lang && auth()->check() && auth()->user()->lang) {
            $lang = auth()->user()->lang;
            session(['lang' => $lang]);
        }

        $lang = $lang ?? config('app.locale', 'ar');

        app()->setLocale($lang);

        return $next($request);
    }
}
