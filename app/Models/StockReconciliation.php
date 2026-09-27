<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReconciliation extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'stock_audit_id',
        'user_id',
        'product_id',
        'stock_id',
        'batch_no',
        'expire_date',
        'adjustment_type',
        'previous_quantity',
        'new_quantity',
        'adjustment_quantity',
        'unit_cost',
        'adjustment_value',
        'reference_type',
        'reference_id',
        'reason',
        'is_posted',
        'posted_at',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function stockAudit(): BelongsTo
    {
        return $this->belongsTo(StockAudit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_posted', false);
    }
}
