<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that verifies a Clerk session token from the Authorization header
 * or a cookie, and authenticates the user via their Clerk ID.
 *
 * This middleware works alongside the existing Laravel auth middleware.
 * It checks for a Bearer token (Clerk session token) and, if valid,
 * logs the user in via the local user record matched by clerk_id.
 *
 * Use this middleware on routes that need to support Clerk-based authentication.
 */
class ClerkSessionAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is already authenticated via Laravel session, pass through
        if (auth()->check()) {
            return $next($request);
        }

        // Try to get Clerk session token from Authorization header
        $token = $request->bearerToken();

        // Fallback: check for __clerk_session cookie (set by Clerk JS)
        if (!$token) {
            $token = $request->cookie('__clerk_session');
        }

        if (!$token) {
            return $next($request);
        }

        try {
            // Verify the Clerk session token using the backend SDK
            $userData = $this->verifyClerkToken($token);

            if ($userData && isset($userData['sub'])) {
                $clerkId = $userData['sub'];

                // Find or create local user by Clerk ID
                $user = User::where('clerk_id', $clerkId)->first();

                if ($user) {
                    // Log the user in via Laravel's auth guard
                    auth()->login($user, true); // true = remember me
                    Log::info("Clerk session authenticated user: {$clerkId}");
                } else {
                    Log::warning("Clerk session token valid but no local user for: {$clerkId}");
                }
            }
        } catch (\Exception $e) {
            Log::warning("Clerk token verification failed: {$e->getMessage()}");
        }

        return $next($request);
    }

    /**
     * Verify a Clerk session token using the Backend API's JWKS endpoint.
     *
     * This uses the clerkinc/backend-php SDK's helper to verify the JWT.
     */
    protected function verifyClerkToken(string $token): ?array
    {
        $secretKey = config('clerk.secret_key', env('CLERK_SECRET_KEY'));

        if (!$secretKey) {
            Log::error('CLERK_SECRET_KEY is not configured');
            return null;
        }

        try {
            // Use Clerk's Backend SDK to verify the token via the JWKS endpoint
            $jwks = \Clerk\Backend\Helpers\Jwks\Jwks::fromSecretKey($secretKey);
            $payload = $jwks->verifyJwt($token);

            return $payload;
        } catch (\Exception $e) {
            Log::warning("Clerk JWT verification error: {$e->getMessage()}");
            return null;
        }
    }
}
