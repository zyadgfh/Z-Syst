<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GoodsReceivedNote - table `goods_received_notes` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class GoodsReceivedNote extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PARTIALLY_ACCEPTED = 'partially_accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACCEPTED = 'accepted';

    use HasFactory;

    use SoftDeletes;

    protected $table = 'goods_received_notes';

    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'supplier_id',
        'grn_number',
        'received_date',
        'location',
        'status',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'supplier_id' => 'integer',
        'received_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GRNItem::class, 'grn_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $supplierId ? $query->where('supplier_id', $supplierId) : $query;
    }

    public function scopeForPurchaseOrder($query, $poId)
    {
        return $poId ? $query->where('purchase_order_id', $poId) : $query;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /*
     * GRN workflow helpers consumed by GRNService (verify → accept/reject)
     * and quantity aggregations used for partial-accept decisions.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isAccepted(): bool
    {
        return in_array($this->status, ['accepted', 'partially_accepted'], true);
    }

    public function markAsVerified(): void
    {
        $this->forceFill(['status' => 'verified', 'verified_at' => now()])->save();
    }

    public function markAsAccepted(): void
    {
        $this->forceFill(['status' => 'accepted'])->save();
    }

    public function markAsPartiallyAccepted(): void
    {
        $this->forceFill(['status' => 'partially_accepted'])->save();
    }

    public function markAsRejected(): void
    {
        $this->forceFill(['status' => 'rejected'])->save();
    }

    public function getTotalReceivedQuantityAttribute(): int
    {
        return (int) $this->items()->sum('received_quantity');
    }

    public function getTotalRejectedQuantityAttribute(): int
    {
        return (int) $this->items()->sum('rejected_quantity');
    }

    public static function generateGRNNumber(): string
    {
        $prefix = 'GRN-';
        $latest = static::query()->latest('id')->first();
        $next = $latest ? ((int) filter_var($latest->getKey(), FILTER_SANITIZE_NUMBER_INT) + 1) : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
