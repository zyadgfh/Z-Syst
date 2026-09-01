<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session;

class Wishlist extends Model
{
    protected $fillable = ['user_id', 'session_id', 'product_id'];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Scopes ──

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForCurrentVisitor($query)
    {
        if (auth()->check()) {
            return $query->where('user_id', auth()->id());
        }

        return $query->where('session_id', Session::getId());
    }

    // ── Static Helpers ──

    public static function isWishlisted(int $productId): bool
    {
        return static::forCurrentVisitor()
            ->where('product_id', $productId)
            ->exists();
    }

    public static function count(): int
    {
        return (int) static::forCurrentVisitor()->count();
    }

    public static function toggle(int $productId): bool
    {
        $exists = static::forCurrentVisitor()
            ->where('product_id', $productId)
            ->first();

        if ($exists) {
            $exists->delete();
            return false; // removed
        }

        static::create([
            'user_id'    => auth()->id(),
            'session_id' => auth()->guest() ? Session::getId() : null,
            'product_id' => $productId,
        ]);

        return true; // added
    }

    /**
     * Merge session wishlists into user wishlists on login.
     */
    public static function mergeSessionToUser(int $userId): void
    {
        $sessionId = Session::getId();

        $sessionItems = static::where('session_id', $sessionId)
            ->where('user_id', null)
            ->pluck('product_id');

        foreach ($sessionItems as $productId) {
            static::firstOrCreate([
                'user_id'    => $userId,
                'product_id' => $productId,
            ]);
        }

        // Clean up session wishlists
        static::where('session_id', $sessionId)->delete();
    }
}
