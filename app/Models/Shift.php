<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Shift extends Model
{
    
    use TenantScoped;
public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $fillable = [
        'business_id', 'user_id', 'shift_number', 'opening_float', 'status',
        'opened_at', 'closed_at', 'expected_cash', 'counted_cash', 'difference', 'notes', 'meta',
    ];

    protected $casts = [
        'opened_at' => 'datetime', 'closed_at' => 'datetime',
        'opening_float' => 'float', 'expected_cash' => 'float',
        'counted_cash' => 'float', 'difference' => 'float',
        'meta' => 'array',
    ];

    public function movements()
    {
        return $this->hasMany(ShiftCashMovement::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Recompute expected cash = float + sales(cash) + cash_in - cash_out - refunds. */
    public function recomputeExpected(): float
    {
        $byType = $this->movements()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $expected = (float) $this->opening_float
            + (float) ($byType['sale_cash'] ?? 0)
            + (float) ($byType['cash_in'] ?? 0)
            - (float) ($byType['cash_out'] ?? 0)
            - (float) ($byType['refund'] ?? 0);

        $this->update(['expected_cash' => $expected]);

        return $expected;
    }
}
