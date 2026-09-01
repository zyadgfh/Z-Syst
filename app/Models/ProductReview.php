<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'customer_order_id',
        'rating',
        'title',
        'review',
        'is_verified_purchase',
        'is_approved',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_count' => 'integer',
    ];

    // ── Relationships ──

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    // ── Scopes ──

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    // ── Static Helpers ──

    /**
     * Get average rating for a product
     */
    public static function getAverageRating(int $productId): ?float
    {
        return static::where('product_id', $productId)
            ->approved()
            ->avg('rating');
    }

    /**
     * Get review count for a product
     */
    public static function getReviewCount(int $productId): int
    {
        return static::where('product_id', $productId)
            ->approved()
            ->count();
    }

    /**
     * Get rating distribution for a product (1-5)
     */
    public static function getRatingDistribution(int $productId): array
    {
        $counts = static::where('product_id', $productId)
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $counts[$i] ?? 0;
        }

        return $distribution;
    }

    /**
     * Check if user can review a product (has ordered it and not reviewed yet)
     */
    public static function canUserReview(int $userId, int $productId): bool
    {
        // Must have ordered the product
        $hasOrdered = CustomerOrderItem::where('product_id', $productId)
            ->whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('status', 'delivered');
            })
            ->exists();

        if (!$hasOrdered) {
            return false;
        }

        // Must not have already reviewed
        $hasReviewed = static::where('product_id', $productId)
            ->where('user_id', $userId)
            ->exists();

        return !$hasReviewed;
    }
}
