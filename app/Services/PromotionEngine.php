<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Server-side promotion evaluation (the client NEVER dictates discounts —
 * H-01 cousin rule). Promotions reduce the sale-level discountAmount so the
 * hardened sale store keeps line prices at list price.
 */
class PromotionEngine
{
    /**
     * @param array $lines  [{product_id, price, quantities}]
     * @return array{discount: float, applied: array, bonus_units: int}
     */
    public function evaluate(int $businessId, array $lines, ?string $couponCode = null): array
    {
        $promotions = Promotion::live($businessId)->get();
        $discount = 0.0;
        $bonusUnits = 0;
        $applied = [];

        foreach ($promotions as $promo) {
            // Coupon-typed promotions only fire when their code is presented;
            // automatic promotions always apply.
            if ($promo->coupon_code && strcasecmp($promo->coupon_code, (string) $couponCode) !== 0) {
                continue;
            }

            foreach ($lines as $line) {
                if (!$this->matches($promo, (int) $line['product_id'], $businessId)) {
                    continue;
                }
                $qty = (int) ($line['quantities'] ?? 0);
                $price = (float) ($line['price'] ?? 0);
                if ($qty < max(1, $promo->min_qty)) {
                    continue;
                }

                switch ($promo->type) {
                    case 'percent':
                        $discount += $price * $qty * ($promo->value / 100);
                        break;
                    case 'fixed':
                        $discount += min($promo->value, $price * $qty);
                        break;
                    case 'qty_bonus':
                        $sets = intdiv($qty, max(1, $promo->min_qty));
                        $bonusUnits += $sets * $promo->bonus_qty;
                        break;
                    case 'near_expiry':
                        // pct off for soon-to-expire batches; the POS passes the
                        // batch expiry so the discount is context aware.
                        $discount += $price * $qty * ($promo->value / 100);
                        break;
                }
            }

            $applied[] = [
                'promotion_id' => $promo->id,
                'name' => $promo->name,
                'type' => $promo->type,
                'coupon' => $promo->coupon_code,
            ];
            $promo->increment('used_count');
        }

        return [
            'discount' => round($discount, 2),
            'applied' => $applied,
            'bonus_units' => $bonusUnits,
        ];
    }

    /** Quote only — never mutates counters (used by the cart preview). */
    public function quote(int $businessId, array $lines, ?string $couponCode = null): array
    {
        $promotions = Promotion::live($businessId)->get();
        $discount = 0.0;
        $bonusUnits = 0;
        $applied = [];

        // Bonus units granted per line — price promos must not double-count
        // units that a qty_bonus promotion already gives away.
        $lineBonus = [];
        foreach ($promotions as $promo) {
            if ($promo->type !== 'qty_bonus') {
                continue;
            }
            foreach ($lines as $i => $line) {
                if (!$this->matches($promo, (int) $line['product_id'], $businessId)) {
                    continue;
                }
                $qty = (int) ($line['quantities'] ?? 0);
                if ($qty >= max(1, $promo->min_qty)) {
                    $lineBonus[$i] = ($lineBonus[$i] ?? 0) + intdiv($qty, max(1, $promo->min_qty)) * $promo->bonus_qty;
                }
            }
        }

        foreach ($promotions as $promo) {
            if ($promo->coupon_code && strcasecmp($promo->coupon_code, (string) $couponCode) !== 0) {
                continue;
            }
            foreach ($lines as $i => $line) {
                if (!$this->matches($promo, (int) $line['product_id'], $businessId)) {
                    continue;
                }
                $qty = (int) ($line['quantities'] ?? 0);
                $price = (float) ($line['price'] ?? 0);
                if ($qty < max(1, $promo->min_qty)) {
                    continue;
                }
                if ($promo->type === 'qty_bonus') {
                    $bonusUnits += intdiv($qty, max(1, $promo->min_qty)) * $promo->bonus_qty;
                    continue;
                }
                // A line covered by a bonus group gives away (min_qty + bonus_qty)
                // units; price promotions apply only to the units past the first
                // benefit group so the two promotions do not stack on them.
                $billable = $qty;
                if (isset($lineBonus[$i]) && $lineBonus[$i] > 0) {
                    $groupPromo = $promotions->firstWhere('type', 'qty_bonus');
                    $billable = max(0, $qty - ($groupPromo->min_qty + $groupPromo->bonus_qty));
                }
                if ($billable <= 0) {
                    continue;
                }
                if ($promo->type === 'percent' || $promo->type === 'near_expiry') {
                    $discount += $price * $billable * ($promo->value / 100);
                } elseif ($promo->type === 'fixed') {
                    $discount += min($promo->value, $price * $billable);
                }
            }
            $applied[] = ['promotion_id' => $promo->id, 'name' => $promo->name, 'type' => $promo->type, 'coupon' => $promo->coupon_code];
        }

        return ['discount' => round($discount, 2), 'applied' => $applied, 'bonus_units' => $bonusUnits];
    }

    private function matches(Promotion $promo, int $productId, int $businessId): bool
    {
        return match ($promo->applies_to) {
            'all' => true,
            'product' => (int) $promo->product_id === $productId,
            'category' => app(\App\Models\Product::class)
                ->where('id', $productId)
                ->where('category_id', $promo->category_id)
                ->exists(),
            default => false,
        };
    }
}
