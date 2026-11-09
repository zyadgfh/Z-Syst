<?php

namespace App\Models;

use App\Services\PurchaseOrderService;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseOrder - table `purchase_orders` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PurchaseOrder extends Model
{
    
    use TenantScoped;
public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AppUser::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AppUser::class, 'approved_by');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /** Line total net of the item-level discount percentage (fresh query —
     *  the items relation is mutated while lines are being added). */
    public function getSubtotalAttribute(): float
    {
        // No rounding: callers compare against the same raw float reduction.
        return (float) $this->items()->get()->sum(
            fn ($item) => ((float) $item->quantity) * ((float) $item->unit_price) * (1 - ((float) $item->discount) / 100)
        );
    }

    public function getCompletionPercentageAttribute(): float
    {
        $qty = (float) $this->items()->get()->sum('quantity');
        if ($qty <= 0.0) {
            return 0.0;
        }

        return round(((float) $this->items()->get()->sum('received_quantity') / $qty) * 100, 2);
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->getSubtotalAttribute();
        $total = $subtotal + (float) $this->tax_amount - (float) $this->discount_amount;

        $this->forceFill([
            'subtotal' => $subtotal,
            'total_amount' => round($total, 2),
        ])->save();

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
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
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

    public function isPartiallyReceived(): bool
    {
        return $this->items()->get()->contains(fn ($item) =>
            (float) $item->received_quantity > 0.0
            && (float) $item->received_quantity < (float) $item->quantity);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function markAsSent(): static
    {
        $this->forceFill(['status' => self::STATUS_SENT, 'sent_at' => now()])->save();

        return $this;
    }

    public function approve($userId = null): static
    {
        // Direct state change — PurchaseOrderService::approve() calls this
        // method, so delegating back to the service would recurse forever.
        $this->forceFill([
            'status' => self::STATUS_ACCEPTED,
            'approved_by' => $userId ?: auth()->id(),
            'approved_at' => now(),
        ])->save();

        return $this->refresh();
    }

    public function reject($userId = null, string $reason = null): static
    {
        // Direct state change — the service delegates here; delegating back
        // would recurse forever.
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_by' => $userId ?: auth()->id(),
            'rejected_at' => now(),
        ])->save();

        return $this->refresh();
    }

    public function cancel(string $reason = null): static
    {
        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'internal_notes' => trim((string) $this->internal_notes . "
" . 'Cancelled: ' . $reason),
        ])->save();

        return $this->refresh();
    }
}

