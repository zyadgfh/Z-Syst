<?php

namespace App\Models;

use App\Scopes\TenantScope;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    // ────────────────── Status Constants ──────────────────
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * All valid status values.
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_SENT,
        self::STATUS_RECEIVED,
        self::STATUS_PARTIAL,
        self::STATUS_CANCELLED,
    ];

    /**
     * Statuses that allow modification.
     */
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_PENDING];

    protected $fillable = [
        'company_id',
        'supplier_id',
        'branch_id',
        'uuid',
        'po_number',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'expected_delivery_date',
        'notes',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function purchaseOrderReturns(): HasMany
    {
        return $this->hasMany(PurchaseOrderReturn::class);
    }

    /**
     * Check if the purchase order can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES);
    }

    /**
     * Check if the purchase order is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    /**
     * Check if the purchase order is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    /**
     * Get the total quantity ordered across all items.
     */
    public function totalOrdered(): float
    {
        return (float) $this->items->sum('quantity_ordered');
    }

    /**
     * Get the total quantity received across all items.
     */
    public function totalReceived(): float
    {
        return (float) $this->items->sum('quantity_received');
    }

    /**
     * Get the remaining quantity to be received.
     */
    public function totalPending(): float
    {
        return $this->totalOrdered() - $this->totalReceived();
    }

    /**
     * Get the reception percentage.
     */
    public function receptionPercentage(): float
    {
        $ordered = $this->totalOrdered();

        return $ordered > 0 ? ($this->totalReceived() / $ordered) * 100 : 0;
    }

    /**
     * Validate a status transition.
     *
     * @throws \InvalidArgumentException
     */
    public function validateStatusTransition(string $newStatus): void
    {
        $allowedTransitions = [
            self::STATUS_DRAFT => [self::STATUS_PENDING, self::STATUS_CANCELLED],
            self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
            self::STATUS_APPROVED => [self::STATUS_SENT, self::STATUS_CANCELLED],
            self::STATUS_SENT => [self::STATUS_RECEIVED, self::STATUS_PARTIAL, self::STATUS_CANCELLED],
            self::STATUS_PARTIAL => [self::STATUS_RECEIVED, self::STATUS_CANCELLED],
            self::STATUS_RECEIVED => [],
            self::STATUS_CANCELLED => [],
        ];

        $allowed = $allowedTransitions[$this->status] ?? [];

        if (! in_array($newStatus, $allowed) && $newStatus !== $this->status) {
            throw new \InvalidArgumentException(
                "Invalid status transition from '{$this->status}' to '{$newStatus}'."
            );
        }
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return self::withoutGlobalScope(TenantScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }
}
