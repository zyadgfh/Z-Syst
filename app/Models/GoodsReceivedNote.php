<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceivedNote extends Model
{
    use BelongsToBusiness;
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'business_id',
        'branch_id',
        'received_by',
        'verified_by',
        'grn_number',
        'received_date',
        'location',
        'status',
        'notes',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'received_date' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the purchase order for the GRN.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier for the GRN.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    /**
     * Get the business that owns the GRN.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the branch that owns the GRN.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who received the GRN.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the user who verified the GRN.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the items for the GRN.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GRNItem::class);
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
     * Scope to filter by purchase order.
     */
    public function scopeForPurchaseOrder($query, $purchaseOrderId)
    {
        return $query->where('purchase_order_id', $purchaseOrderId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending GRNs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter verified GRNs.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Check if GRN is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if GRN is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Check if GRN is accepted.
     */
    public function isAccepted(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_PARTIALLY_ACCEPTED]);
    }

    /**
     * Check if GRN is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Mark GRN as verified.
     */
    public function markAsVerified(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark GRN as accepted.
     */
    public function markAsAccepted(): void
    {
        $this->update(['status' => self::STATUS_ACCEPTED]);
    }

    /**
     * Mark GRN as partially accepted.
     */
    public function markAsPartiallyAccepted(): void
    {
        $this->update(['status' => self::STATUS_PARTIALLY_ACCEPTED]);
    }

    /**
     * Mark GRN as rejected.
     */
    public function markAsRejected(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Generate GRN number.
     */
    public static function generateGRNNumber(): string
    {
        $date = now()->format('Ymd');
        $lastGRN = self::where('grn_number', 'like', "GRN-{$date}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastGRN) {
            $lastNumber = (int) substr($lastGRN->grn_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return "GRN-{$date}-{$newNumber}";
    }

    /**
     * Get total received quantity.
     */
    public function getTotalReceivedQuantityAttribute(): int
    {
        return $this->items->sum('received_quantity');
    }

    /**
     * Get total accepted quantity.
     */
    public function getTotalAcceptedQuantityAttribute(): int
    {
        return $this->items->sum('accepted_quantity');
    }

    /**
     * Get total rejected quantity.
     */
    public function getTotalRejectedQuantityAttribute(): int
    {
        return $this->items->sum('rejected_quantity');
    }

    /**
     * Get total value.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->accepted_quantity * $item->purchase_price;
        });
    }

    /**
     * Calculate completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        $totalOrdered = $this->items->sum('ordered_quantity');
        if ($totalOrdered === 0) return 0;

        $totalReceived = $this->items->sum('received_quantity');
        return ($totalReceived / $totalOrdered) * 100;
    }
}
