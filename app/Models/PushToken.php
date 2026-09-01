<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'token',
        'platform',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'last_used_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get all active tokens for a business.
     */
    public static function forBusiness(int $businessId)
    {
        return static::where('business_id', $businessId)
            ->where('is_active', true);
    }

    /**
     * Register or update a push token for a user.
     */
    public static function register(int $userId, string $token, string $platform = 'web'): static
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'token' => $token],
            [
                'business_id'  => auth()->user()->business_id ?? null,
                'platform'     => $platform,
                'is_active'    => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Deactivate a push token.
     */
    public static function deactivate(string $token): bool
    {
        return (bool) static::where('token', $token)->update(['is_active' => false]);
    }
}
