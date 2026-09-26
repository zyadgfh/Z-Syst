<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAuditDetail extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'stock_audit_id',
        'business_id',
        'product_id',
        'stock_id',
        'batch_no',
        'expire_date',
        'system_quantity',
        'physical_quantity',
        'variance',
        'unit_cost',
        'variance_value',
        'variance_type',
        'notes',
    ];

    protected $casts = [
        'expire_date' => 'date',
    ];

    public function stockAudit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function calculateVariance()
    {
        $this->variance = $this->physical_quantity - $this->system_quantity;
        $this->variance_value = $this->variance * $this->unit_cost;

        if ($this->variance > 0) {
            $this->variance_type = 'positive';
        } elseif ($this->variance < 0) {
            $this->variance_type = 'negative';
        } else {
            $this->variance_type = 'none';
        }

        return $this;
    }
}
