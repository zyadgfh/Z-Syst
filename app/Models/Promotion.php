<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Promotion extends Model
{
    
    use TenantScoped;
protected $fillable = [
        'business_id', 'name', 'type', 'value', 'min_qty', 'bonus_qty',
        'applies_to', 'product_id', 'category_id', 'coupon_code',
        'starts_at', 'ends_at', 'max_uses', 'used_count', 'is_active', 'meta',
    ];

    protected $casts = [
        'value' => 'float', 'starts_at' => 'datetime', 'ends_at' => 'datetime',
        'is_active' => 'boolean', 'meta' => 'array',
    ];

    /** Active, within window, and under usage cap. */
    public function scopeLive(Builder $q, int $businessId): Builder
    {
        $now = now();

        return $q->where('business_id', $businessId)
            ->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->where(fn ($w) => $w->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));
    }
}
