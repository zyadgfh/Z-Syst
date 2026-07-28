<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderReturn extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    // ────────────────── Status Constants ──────────────────
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * All valid status values.
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_APPROVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'supplier_id',
        'branch_id',
        'return_number',
        'notes',
        'created_by',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderReturnItem::class);
    }

    /**
     * Get the total amount of all return items.
     */
    public function totalAmount(): float
    {
        return (float) $this->items->sum('total');
    }

    /**
     * Get the total quantity returned across all items.
     */
    public function totalQuantityReturned(): float
    {
        return (float) $this->items->sum('quantity_returned');
    }

    /**
     * Check if the return can be edited (only in draft status).
     */
    public function canEdit(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
