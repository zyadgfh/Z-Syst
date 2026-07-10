<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!moduleCheck('CustomDomainAddon')) {
            return $next($request);
        }

        $host = $request->getHost(); // Current host
        $installedDomain = get_root_domain();

        if (!$installedDomain) {
            abort(406, 'Error: App URL not detected. Please update the APP_URL value in your .env file.');
        }

        // Allow the exact installed domain
        if ($host === $installedDomain) {
            return $next($request);
        }

        // Otherwise check verified addon/custom domains
        $isAllowed = \Modules\CustomDomainAddon\App\Models\Domain::query()
                        ->where('domain', $host)
                        ->where('is_verified', 1)
                        ->where('status', 1)
                        ->exists();

        if (!$isAllowed) {
            abort(400, 'Error: this domain is not allowed. Please request for a domain/subdomain from the business panel.');
        }

        $publicRoutes = [
            '/',
            'blogs',
            'blogs/*',
            'about-us',
            'plans',
            'data-deletion',
            'terms-conditions',
            'privacy-policy',
            'contact-us',
        ];

        if ($request->is($publicRoutes)) {
            return redirect('/login');
        }

        return $next($request);
    }
}
