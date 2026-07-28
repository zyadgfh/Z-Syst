<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTurnoverReport extends Model
{
    use HasFactory;

    protected $table = 'inventory_turnover_reports';

    protected $fillable = [
        'business_id',
        'report_type',
        'period_start',
        'period_end',
        'total_cogs',
        'average_inventory_value',
        'inventory_turnover_ratio',
        'days_inventory_outstanding',
        'total_products_analyzed',
        'slow_moving_count',
        'dead_stock_count',
        'total_slow_moving_value',
        'total_dead_stock_value',
        'total_inventory_value',
        'meta',
    ];

    protected $casts = [
        'total_cogs' => 'decimal:2',
        'average_inventory_value' => 'decimal:2',
        'inventory_turnover_ratio' => 'decimal:2',
        'days_inventory_outstanding' => 'decimal:2',
        'total_slow_moving_value' => 'decimal:2',
        'total_dead_stock_value' => 'decimal:2',
        'total_inventory_value' => 'decimal:2',
        'meta' => 'json',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function productAnalyses(): HasMany
    {
        return $this->hasMany(ProductInventoryAnalysis::class, 'report_id');
    }
}

