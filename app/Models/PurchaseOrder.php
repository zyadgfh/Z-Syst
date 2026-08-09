<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'business_id',
        'branch_id',
        'created_by',
        'approved_by',
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
        'shipping_address',
        'shipping_cost',
        'tax',
        'approved_at',
        'sent_at',
        'cancelled_at',
        'rejected_at',
        'rejection_reason',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_ACCEPTED = 'accepted';

    const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    const STATUS_RECEIVED = 'received';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REJECTED = 'rejected';

    /**
     * Priority constants
     */
    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the supplier that owns the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    /**
     * Get the business that owns the purchase order.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the purchase order.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the purchase order.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the purchase order.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the items for the purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Scope to filter by business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter by supplier.
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter active orders.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter draft orders.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to filter pending orders.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * Scope to filter approved orders.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_ACCEPTED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED]);
    }

    /**
     * Scope to filter overdue orders.
     */
    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
            ->whereIn('status', [self::STATUS_SENT, self::STATUS_ACCEPTED, self::STATUS_PARTIALLY_RECEIVED]);
    }

    /**
     * Check if PO is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if PO is sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if PO is approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED]);
    }

    /**
     * Check if PO is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if PO is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if PO is received.
     */
    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    /**
     * Check if PO is partially received.
     */
    public function isPartiallyReceived(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_RECEIVED;
    }

    /**
     * Mark PO as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Approve PO.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject PO.
     */
    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel PO.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark PO as partially received.
     */
    public function markAsPartiallyReceived(): void
    {
        $this->update([
            'status' => self::STATUS_PARTIALLY_RECEIVED,
        ]);
    }

    /**
     * Mark PO as received.
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => self::STATUS_RECEIVED,
            'actual_delivery_date' => now(),
        ]);
    }

    /**
     * Calculate total amount.
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->items()->sum('total');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    /**
     * Get total quantity.
     */
    public function getTotalQuantity(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get received quantity.
     */
    public function getReceivedQuantity(): int
    {
        return $this->items()->sum('received_quantity');
    }

    /**
     * Get pending quantity.
     */
    public function getPendingQuantity(): int
    {
        return $this->items()->sum('pending_quantity');
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $total = $this->getTotalQuantity();
        if ($total === 0) {
            return 0;
        }

        $received = $this->getReceivedQuantity();

        return ($received / $total) * 100;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (auth()->check()) {
                $po->created_by = auth()->id();
            }
            if (empty($po->po_number)) {
                $po->po_number = self::generatePONumber($po->business_id);
            }
        });

        static::updating(function ($po) {
            if (auth()->check()) {
                // Handle approval/rejection logic
                if ($po->isDirty('status') && $po->status === self::STATUS_ACCEPTED) {
                    $po->approved_by = auth()->id();
                    $po->approved_at = now();
                }
            }
        });
    }

    /**
     * Generate PO number.
     */
    private static function generatePONumber(int $businessId): string
    {
        $count = self::where('business_id', $businessId)->count() + 1;

        return 'PO-'.date('Y').'-'.str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
