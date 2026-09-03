<?php

namespace App\Http\Middleware;

use App\Services\PageOptimizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageOptimizationMiddleware
{
    protected PageOptimizationService $optimizationService;

    public function __construct(PageOptimizationService $optimizationService)
    {
        $this->optimizationService = $optimizationService;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only optimize HTML responses
        if ($response instanceof Response && $response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            $content = $response->getContent();

            // Minify HTML in production
            if (app()->environment('production')) {
                $content = $this->optimizationService->minifyHTML($content);
            }

            $response->setContent($content);
        }

        return $response;
    }
}
