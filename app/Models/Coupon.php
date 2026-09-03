<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id',
        'code',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'times_used',
        'starts_at',
        'expires_at',
        'active',
        'single_use',
    ];

    protected $casts = [
        'value'                 => 'decimal:2',
        'minimum_order_amount'  => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'starts_at'             => 'datetime',
        'expires_at'            => 'datetime',
        'active'                => 'boolean',
        'single_use'            => 'boolean',
    ];

    // ── Relationships ──

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ── Validation ──

    /**
     * Validate and apply coupon to an order subtotal.
     * Returns ['valid' => bool, 'discount' => float, 'message' => string]
     */
    public static function validateAndApply(string $code, float $subtotal, ?int $userId = null): array
    {
        $coupon = static::where('code', strtoupper(trim($code)))
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'message' => 'الكوبون غير صالح'];
        }

        // Check date validity
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'الكوبون لم يبدأ بعد'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['valid' => false, 'discount' => 0, 'message' => 'انتهت صلاحية الكوبون'];
        }

        // Check global usage limit
        if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
            return ['valid' => false, 'discount' => 0, 'message' => 'تم استخدام الكوبون بالفعل'];
        }

        // Check per-user usage
        if ($userId && $coupon->usage_limit_per_user) {
            $userUses = $coupon->usages()->where('user_id', $userId)->count();
            if ($userUses >= $coupon->usage_limit_per_user) {
                return ['valid' => false, 'discount' => 0, 'message' => 'لقد استخدمت هذا الكوبون من قبل'];
            }
        }

        // Check minimum order amount
        if ($subtotal < $coupon->minimum_order_amount) {
            return [
                'valid'   => false,
                'discount' => 0,
                'message' => 'الحد الأدنى للطلب هو ' . number_format($coupon->minimum_order_amount, 2),
            ];
        }

        // Calculate discount
        $discount = match ($coupon->type) {
            'percentage' => min(
                $subtotal * ($coupon->value / 100),
                $coupon->maximum_discount_amount ?? PHP_FLOAT_MAX
            ),
            'fixed' => min($coupon->value, $subtotal),
            default => 0,
        };

        $discount = round($discount, 2);

        return [
            'valid'    => true,
            'discount' => $discount,
            'coupon'   => $coupon,
            'message'  => 'تم تطبيق الكوبون بنجاح',
        ];
    }

    public function isCurrentlyValid(): bool
    {
        if (!$this->active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->times_used >= $this->usage_limit) return false;
        return true;
    }
}
