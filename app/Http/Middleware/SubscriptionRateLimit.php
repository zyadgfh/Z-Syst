<?php

namespace App\Http\Middleware;

use App\Models\SubscriptionPlan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $maxAttempts
     */
    public function handle(Request $request, Closure $next, $maxAttempts = null): Response
    {
        $user = $request->user();
        
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Get rate limit based on subscription plan or company settings
        $attempts = $this->getRateLimitForTenant($user);

        // Skip rate limiting for unlimited plans
        if ($attempts === -1) {
            return $next($request);
        }

        $key = $this->getRateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key);

        $response = $next($request);

        return $this->setHeaders($response, $key, $attempts);
    }

    /**
     * Get rate limit based on subscription plan.
     */
    protected function getRateLimitForTenant($user): int
    {
        $company = $user->company;
        
        if (! $company) {
            return config('api.rate_limits.free', 100); // Default: 100 requests per minute
        }

        // Check subscription plan for rate limit
        $subscription = $company->subscriptionLatest;
        
        if ($subscription && $subscription->plan) {
            return $subscription->plan->rate_limit ?? config('api.rate_limits.free', 100);
        }

        // Use config-based limits
        $rateLimits = config('api.rate_limits', [
            'free' => 100,
            'starter' => 1000,
            'professional' => 10000,
            'enterprise' => -1, // unlimited
        ]);

        // Return dynamic rate limit based on company's branch count or plan
        if ($company->is_unlimited_branches) {
            return $rateLimits['enterprise'];
        }

        $branchCount = $company->current_branches_count ?? 0;
        
        if ($branchCount >= 50) {
            return $rateLimits['enterprise'];
        } elseif ($branchCount >= 10) {
            return $rateLimits['professional'];
        } elseif ($branchCount >= 1) {
            return $rateLimits['starter'];
        }

        return $rateLimits['free'];
    }

    /**
     * Get rate limit key for the request.
     */
    protected function getRateLimitKey(Request $request): string
    {
        $user = $request->user();

        return 'api.v1.'.$user->company_id.'.'.$user->id;
    }

    /**
     * Set rate limit headers on response.
     */
    protected function setHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - RateLimiter::attempts($key)));
        $response->headers->set('X-RateLimit-Reset', RateLimiter::availableIn($key));

        return $response;
    }
}