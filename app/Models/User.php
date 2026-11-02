<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

/**
 * Canonical user model.
 *
 * Historically App\Models\User mapped the MySQL `users` table and carried
 * email, password and remember_token. After the cutover there is no `users`
 * table: identity is auth.users (GoTrue) and the application profile is
 * public.app_users. This class therefore extends AppUser so that the ~30
 * existing call sites keep working, and bridges the two:
 *
 *   - $user->email            -> read-through to auth.users (cached per request)
 *   - User::whereEmail($e)    -> resolves through auth.users, then app_users
 *   - $user->password = ...   -> throws; passwords are set via GoTrue only
 *
 * The class is NOT removed on purpose: renaming it everywhere would be a
 * large, risky change with no functional gain.
 */
class User extends AppUser
{
    use HasApiTokens;

    protected $table = 'app_users';

    /**
     * Request-scoped cache so a table of 50 users runs one auth.users query,
     * not fifty.
     *
     * @var array<string, string|null>
     */
    protected static array $emailCache = [];

    /**
     * Email lives in auth.users; expose it as a normal attribute.
     */
    public function getEmailAttribute(): ?string
    {
        // Local fallback mode stores the email on the row itself.
        if (config('supabase.fallback_eloquent')) {
            return $this->attributes['email'] ?? null;
        }

        $id = $this->getKey();

        if (! $id) {
            return null;
        }

        $id = (string) $id;

        if (! array_key_exists($id, static::$emailCache)) {
            static::$emailCache[$id] = AuthUser::emailFor($id);
        }

        return static::$emailCache[$id];
    }

    public function hasAttribute($key): bool
    {
        if ($key === 'email') {
            return true;
        }

        return parent::hasAttribute($key);
    }

    /**
     * Constrain a query to the users whose auth.users email matches.
     */
    public function scopeWhereEmail(Builder $query, string $email): Builder
    {
        if (config('supabase.fallback_eloquent')) {
            return $query->where('email', $email);
        }

        return $query->whereIn($this->getQualifiedKeyName(), AuthUser::idsForEmail($email));
    }

    public static function firstByEmail(string $email): ?self
    {
        return static::query()->whereEmail($email)->first();
    }

    /**
     * Password resets are handled by Supabase; never store a hash locally.
     *
     * @throws RuntimeException always
     */
    public function setEmailAttribute($value): void
    {
        if (config('supabase.fallback_eloquent')) {
            $this->attributes['email'] = $value;

            return;
        }

        throw new RuntimeException(
            'Emails live in auth.users (Path B). Create the identity in Supabase instead.'
        );
    }

    public function setPasswordAttribute($value): void
    {
        if (config('supabase.fallback_eloquent')) {
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make((string) $value);

            return;
        }

        throw new RuntimeException(
            'Passwords are managed by Supabase Auth. Use SupabaseAuthClient::recover() / updatePassword().'
        );
    }

    public function getEmailForPasswordReset(): ?string
    {
        return $this->getEmailAttribute();
    }

    /**
     * Forget the per-request email cache (used by tests).
     */
    public static function flushEmailCache(): void
    {
        static::$emailCache = [];
    }
}
