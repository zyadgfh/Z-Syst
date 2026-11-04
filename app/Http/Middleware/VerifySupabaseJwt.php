<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate API requests with a Supabase access token.
 *
 * This intentionally supports Supabase's HS256 JWT secret path only. Tokens
 * signed with another algorithm are rejected rather than falling back to an
 * unverified decode. The service-role key must never be used as this secret.
 */
class VerifySupabaseJwt
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearerToken($request);
        $claims = $token ? $this->verify($token) : null;

        if (! $claims) {
            throw new AuthenticationException('Invalid or missing Supabase access token.');
        }

        $subject = $claims['sub'] ?? null;
        if (! is_string($subject) || $subject === '') {
            throw new AuthenticationException('Supabase token has no subject.');
        }

        $user = User::query()->find($subject);
        if (! $user || ($user->status ?? 'active') !== 'active') {
            throw new AuthenticationException('Supabase user is not provisioned or is inactive.');
        }

        // Keep Laravel's request and API guard in sync for controllers and
        // downstream tenant/RLS middleware.
        $this->auth->shouldUse('api');
        $this->auth->guard('api')->setUser($user);
        $request->setUserResolver(static fn (?string $guard = null) => $guard
            ? app(AuthManager::class)->guard($guard)->user()
            : $user);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = trim((string) $request->header('Authorization', ''));

        if (! preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Return verified claims or null. No claim is trusted before the HMAC and
     * registered-claim checks succeed.
     */
    private function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJson($encodedHeader);
        $claims = $this->decodeJson($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);
        $secret = (string) config('supabase.jwt.secret');

        if (! $header || ! $claims || $signature === null || $secret === '') {
            return null;
        }

        if (($header['alg'] ?? null) !== 'HS256' || ($header['typ'] ?? 'JWT') !== 'JWT') {
            return null;
        }

        $expected = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $secret, true);
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $now = time();
        $leeway = max(0, (int) config('supabase.jwt.leeway', 10));
        $issuer = config('supabase.jwt.issuer');
        $audience = config('supabase.jwt.audience');

        if ($issuer && ($claims['iss'] ?? null) !== $issuer) {
            return null;
        }

        $tokenAudiences = $claims['aud'] ?? null;
        $tokenAudiences = is_array($tokenAudiences) ? $tokenAudiences : [$tokenAudiences];
        if ($audience && ! in_array($audience, $tokenAudiences, true)) {
            return null;
        }

        if (! isset($claims['exp']) || ! is_numeric($claims['exp']) || (int) $claims['exp'] < $now - $leeway) {
            return null;
        }

        if (isset($claims['nbf']) && (! is_numeric($claims['nbf']) || (int) $claims['nbf'] > $now + $leeway)) {
            return null;
        }

        if (isset($claims['iat']) && (! is_numeric($claims['iat']) || (int) $claims['iat'] > $now + $leeway)) {
            return null;
        }

        return $claims;
    }

    private function decodeJson(string $value): ?array
    {
        $decoded = $this->base64UrlDecode($value);
        if ($decoded === null) {
            return null;
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }

    private function base64UrlDecode(string $value): ?string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);

        return $decoded === false ? null : $decoded;
    }
}
