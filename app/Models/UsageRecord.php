<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'subscription_id', 'metric_name', 'quantity', 'recorded_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric_name', $metric);
    }

    public function scopeForPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }
}
