<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get API version from route
        $version = $request->route()->getPrefix();
        
        // Check if version is specified
        if (! str_starts_with($version, '/v')) {
            return $next($request);
        }

        $versionNumber = ltrim($version, '/v');

        // Add version headers
        $response = $next($request);
        
        $response->headers->set('API-Version', $versionNumber);
        $response->headers->set('X-API-Version', $versionNumber);

        // Check for deprecated versions
        $deprecatedVersions = config('api.deprecated_versions', []);
        
        if (in_array($versionNumber, $deprecatedVersions)) {
            $response->headers->set('X-API-Deprecated', 'true');
            $response->headers->set('X-API-Deprecation-Date', config('api.deprecation_dates.' . $versionNumber));
            $response->headers->set('X-API-Sunset-Date', config('api.sunset_dates.' . $versionNumber));
        }

        return $response;
    }
}
