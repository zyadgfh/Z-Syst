<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'business_id',
        'on_time_delivery_rate',
        'quality_score',
        'price_competitiveness',
        'responsiveness',
        'total_orders',
        'total_disputes',
        'calculated_at',
    ];

    protected $casts = [
        'on_time_delivery_rate' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'price_competitiveness' => 'decimal:2',
        'responsiveness' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function getOverallScoreAttribute(): float
    {
        return (
            $this->on_time_delivery_rate * 0.4 +
            $this->quality_score * 0.3 +
            $this->price_competitiveness * 0.2 +
            $this->responsiveness * 0.1
        );
    }
}
