<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    protected $fillable = [
        'business_id', 'name', 'start_date', 'end_date',
        'is_closed', 'closed_at', 'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Check if a date falls within this period.
     */
    public function containsDate(string $date): bool
    {
        return $date >= $this->start_date->toDateString() && $date <= $this->end_date->toDateString();
    }

    /**
     * Get current open period for a business.
     */
    public static function getCurrentPeriod(int $businessId): ?self
    {
        return static::where('business_id', $businessId)
            ->where('is_closed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }
}
