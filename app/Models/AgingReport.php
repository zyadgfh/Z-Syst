<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgingReport extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'supplier_id', 'business_id', 'report_date', 'period_30',
        'period_60', 'period_90', 'period_90_plus', 'total', 'generated_at',
    ];

    protected $casts = [
        'report_date' => 'datetime',
        'period_30' => 'decimal:2',
        'period_60' => 'decimal:2',
        'period_90' => 'decimal:2',
        'period_90_plus' => 'decimal:2',
        'total' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }

    public function scopeForBusiness($query, $businessId) { return $query->where('business_id', $businessId); }
}
