<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoOrderSuggestion extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'preferred_supplier_id',
        'predicted_demand',
        'current_stock',
        'pending_purchases',
        'suggested_order_qty',
        'confidence_score',
        'priority',
        'status',
        'approved_by',
        'approved_at',
        'converted_purchase_id',
        'converted_at',
        'notes',
        'reasoning',
    ];

    protected $casts = [
        'predicted_demand' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'pending_purchases' => 'decimal:2',
        'suggested_order_qty' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
        'reasoning' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'preferred_supplier_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function convertedPurchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'converted_purchase_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}
