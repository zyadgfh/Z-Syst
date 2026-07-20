<?php

namespace App\Modules\Auth\Application\Services;

use App\Core\Abstracts\AbstractService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService extends AbstractService
{
    /**
     * Verify user credentials.
     */
    public function verifyCredentials(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        return Hash::check($password, $user->password);
    }

    /**
     * Create authentication token for user.
     */
    public function createToken(User $user, string $tokenName = 'auth_token'): string
    {
        return $user->createToken($tokenName)->plainTextToken;
    }

    /**
     * Revoke all tokens for user.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Revoke specific token.
     */
    public function revokeToken(User $user, string $tokenId): void
    {
        $user->tokens()->where('id', $tokenId)->delete();
    }

    /**
     * Dispatch authentication event.
     */
    public function dispatchAuthenticationEvent(User $user): void
    {
        $this->dispatchEvent(new \App\Core\Events\UserAuthenticated($user));
    }

    protected function initializeRepository()
    {
        // Auth service doesn't use repository pattern directly
    }
}
