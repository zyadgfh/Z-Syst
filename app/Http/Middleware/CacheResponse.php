<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Add cache headers to speed up page loads.
     * Used for public-facing pages (home, catalog, blog, etc.)
     */
    public function handle(Request $request, Closure $next, int $minutes = 60): Response
    {
        $response = $next($request);

        if ($response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age=" . ($minutes * 60));
            $response->headers->set('X-Cache-TTL', $minutes * 60);
        }

        return $response;
    }
}
