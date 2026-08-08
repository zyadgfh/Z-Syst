<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'product_id',
        'forecast_date',
        'predicted_quantity',
        'predicted_revenue',
        'confidence_score',
        'lower_bound',
        'upper_bound',
        'method_used',
        'factors',
        'is_active',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_quantity' => 'decimal:2',
        'predicted_revenue' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'lower_bound' => 'decimal:2',
        'upper_bound' => 'decimal:2',
        'is_active' => 'boolean',
        'factors' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
