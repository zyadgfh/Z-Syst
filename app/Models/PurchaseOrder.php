<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrder - table `purchase_orders` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseOrder extends Model
{
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    use HasFactory;

    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'po_number',
        'status',
        'priority',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'terms',
        'internal_notes',
        'notes',
        'approved_at',
        'sent_at',
        'cancelled_at',
        'rejected_at',
        'rejection_reason',
        'is_active',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function calculateTotal(): float
    {
        $total = (float) $this->items()->get()->sum(fn ($item) => ((float) $item->quantity) * ((float) $item->unit_price));
        if ($total <= 0.0 && isset($this->attributes['total_amount'])) {
            $total = (float) $this->attributes['total_amount'];
        }

        return round($total, 2);
    }

    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isSent(): bool { return $this->status === self::STATUS_SENT; }
    public function isAccepted(): bool { return $this->status === self::STATUS_ACCEPTED; }
    public function isReceived(): bool { return $this->status === self::STATUS_RECEIVED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_ACCEPTED]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_ACCEPTED])
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now());
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $supplierId ? $query->where('supplier_id', $supplierId) : $query;
    }
}

