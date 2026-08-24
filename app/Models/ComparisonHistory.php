<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ComparisonHistory extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'product_ids',
        'share_token',
        'view_count',
        'last_viewed_at',
    ];

    protected $casts = [
        'product_ids'    => 'array',
        'view_count'     => 'integer',
        'last_viewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for this comparison.
     */
    public function products()
    {
        return \App\Models\Product::whereIn('id', $this->product_ids ?? [])->get();
    }

    /**
     * Get a comparison by share token.
     */
    public static function findByShareToken(string $token): ?static
    {
        $record = static::where('share_token', $token)->first();
        if ($record) {
            $record->increment('view_count');
            $record->update(['last_viewed_at' => now()]);
        }
        return $record;
    }

    /**
     * Save a comparison to history.
     */
    public static function saveComparison(array $productIds, ?int $userId = null, ?string $sessionId = null): static
    {
        $sortedIds = collect($productIds)->map(fn($id) => (int) $id)->unique()->sort()->values()->toArray();

        // Check if user already has this exact comparison
        $existing = static::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->get()
            ->first(fn($h) => collect($h->product_ids ?? [])->sort()->values()->toArray() === $sortedIds);

        if ($existing) {
            $existing->update(['last_viewed_at' => now()]);
            return $existing;
        }

        return static::create([
            'user_id'       => $userId,
            'session_id'    => $sessionId,
            'product_ids'   => $sortedIds,
            'share_token'   => Str::random(32),
            'last_viewed_at' => now(),
        ]);
    }
}
