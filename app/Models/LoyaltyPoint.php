<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPoint extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'points',
        'type',
        'description',
        'customer_order_id',
        'reference',
        'expires_at',
        'expiration_notification_sent',
    ];

    protected $casts = [
        'points'                       => 'integer',
        'expires_at'                   => 'datetime',
        'expiration_notification_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    /**
     * Award points for a completed order.
     * Default: 1 point per $1 spent.
     */
    public static function awardForOrder(CustomerOrder $order, int $pointsPerDollar = 1): void
    {
        if (!$order->user_id || $order->status === 'cancelled') return;

        $points = (int) floor($order->total_amount * $pointsPerDollar);
        if ($points <= 0) return;

        static::create([
            'user_id'           => $order->user_id,
            'business_id'       => $order->business_id,
            'points'            => $points,
            'type'              => 'earned',
            'description'       => "نقاط الطلب #{$order->order_number}",
            'customer_order_id' => $order->id,
            'reference'         => "order:{$order->id}",
            'expires_at'        => now()->addMonths(12),
        ]);

        // Update balance
        $order->user()->increment('loyalty_points_balance', $points);
    }

    /**
     * Redeem points as a discount amount.
     * Default: 100 points = $1 discount.
     */
    public static function redeem(User $user, int $points, float $discountAmount, ?string $description = null): bool
    {
        if ($user->loyalty_points_balance < $points) return false;

        static::create([
            'user_id'     => $user->id,
            'business_id' => $user->business_id,
            'points'      => -$points,
            'type'        => 'redeemed',
            'description' => $description ?? "استبدال {$points} نقطة",
            'reference'   => 'manual',
            'expires_at'  => null,
        ]);

        $user->decrement('loyalty_points_balance', $points);

        return true;
    }

    public static function getPointsPerDollar(): int
    {
        return (int) (get_option('loyalty')['points_per_dollar'] ?? 1);
    }

    public static function getRedemptionRate(): int
    {
        // How many points = $1 discount
        return (int) (get_option('loyalty')['redemption_rate'] ?? 100);
    }

    /**
     * Scope: points that are expiring soon (within the given days).
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('type', 'earned')
            ->where('points', '>', 0)
            ->where('expires_at', '!=', null)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expiration_notification_sent', false);
    }

    /**
     * Scope: points that have expired.
     */
    public function scopeExpired($query)
    {
        return $query->where('type', 'earned')
            ->where('points', '>', 0)
            ->where('expires_at', '!=', null)
            ->where('expires_at', '<=', now());
    }
}
