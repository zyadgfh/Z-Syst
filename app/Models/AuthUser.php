<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Read-only handle on Supabase `auth.users`.
 *
 * Under Path B identity lives in GoTrue, not in public.app_users: that table
 * carries business_id + role, while the email address — which Laravel still
 * needs for mail, exports and password recovery — stays in auth.users.
 *
 * This model exists so legacy code can resolve "who has this email" without
 * duplicating the address into public tables (which would drift).
 *
 * Everything here is READ ONLY. Writes must go through GoTrue.
 */
class AuthUser extends Model
{
    protected $table = 'auth.users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = [
        'encrypted_password',
        'confirmation_token',
        'recovery_token',
        'email_change',
        'email_change_token_new',
        'email_change_token_current',
        'email_change_confirm_status',
        'phone_change',
        'phone_change_token',
        'reauthentication_token',
    ];

    protected $casts = [
        'email_confirmed_at' => 'datetime',
        'last_sign_in_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function save(array $options = []): bool
    {
        throw new RuntimeException('auth.users is owned by Supabase GoTrue; use SupabaseAuthClient instead.');
    }

    public function delete(): ?bool
    {
        throw new RuntimeException('auth.users is owned by Supabase GoTrue; use the Admin API instead.');
    }

    /**
     * Ids of every auth.users row matching an address (case-insensitive).
     *
     * @return array<int, string>
     */
    public static function idsForEmail(string $email): array
    {
        return static::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
            ->pluck('id')
            ->all();
    }

    public static function emailFor(string $id): ?string
    {
        $email = static::query()->where('id', $id)->value('email');

        return $email === null ? null : (string) $email;
    }
}
