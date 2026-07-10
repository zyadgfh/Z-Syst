<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Redirect to install if not installed
        if (($request->is('/') || $request->is('login')) && !file_exists(storage_path('installed'))) {
            return redirect('install');
        }

        // If DB connection fails → skip locale logic
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            app()->setLocale('en');
            return $next($request);
        }

        // If options table does NOT exist → fallback
        if (!Schema::hasTable('options')) {
            app()->setLocale('en');
            return $next($request);
        }

        // ---------- Locale Logic ----------
        if (auth()->check()) {

            if ($request->has('lang')) {
                auth()->user()->update(['lang' => $request->lang]);
            }

            $lang = auth()->user()->lang
                ?? get_option('general')['default_lang']
                ?? 'en';

        } elseif ($request->has('lang') || session()->has('lang')) {

            if ($request->has('lang')) {
                session(['lang' => $request->lang]);
            }

            $lang = session('lang')
                ?? get_option('general')['default_lang']
                ?? 'en';

        } else {
            $lang = get_option('general')['default_lang'] ?? 'en';
        }

        app()->setLocale($lang);

        return $next($request);
    }
}
