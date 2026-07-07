<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Prunable;

/**
 * StockTransfer Model
 * 
 * Represents a stock transfer between branches within a company.
 * Follows the workflow: pending → approved → in_transit → received
 * 
 * @property int $id
 * @property int $company_id
 * @property int $from_branch_id
 * @property int $to_branch_id
 * @property int $requested_by
 * @property int|null $approved_by
 * @property int|null $shipped_by
 * @property int|null $received_by
 * @property string $transfer_number
 * @property string $status
 * @property string|null $notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon $requested_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $shipped_at
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property float $total_items
 * @property float $total_quantity
 * @property float $total_value
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class StockTransfer extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'from_branch_id',
        'to_branch_id',
        'requested_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'transfer_number',
        'status',
        'notes',
        'rejection_reason',
        'requested_at',
        'approved_at',
        'rejected_at',
        'shipped_at',
        'received_at',
        'cancelled_at',
        'total_items',
        'total_quantity',
        'total_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_items' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    /**
     * Get the company that owns the stock transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that is sending the stock.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * Get the branch that is receiving the stock.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * Get the user who requested the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who shipped the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    /**
     * Get the user who received the transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the items in the stock transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Scope a query to only include transfers with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending transfers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved transfers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include in-transit transfers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    /**
     * Scope a query to only include received transfers.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope a query to only include transfers for a specific branch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('from_branch_id', $branchId)
              ->orWhere('to_branch_id', $branchId);
        });
    }

    /**
     * Scope a query to only include transfers from a specific branch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromBranch($query, int $branchId)
    {
        return $query->where('from_branch_id', $branchId);
    }

    /**
     * Scope a query to only include transfers to a specific branch.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToBranch($query, int $branchId)
    {
        return $query->where('to_branch_id', $branchId);
    }

    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subYears(2));
    }

    /**
     * Check if the transfer can be approved.
     *
     * @return bool
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transfer can be rejected.
     *
     * @return bool
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the transfer can be shipped.
     *
     * @return bool
     */
    public function canBeShipped(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the transfer can be received.
     *
     * @return bool
     */
    public function canBeReceived(): bool
    {
        return $this->status === 'in_transit';
    }

    /**
     * Check if the transfer can be cancelled.
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved', 'in_transit']);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateTransferNumber($transfer->company_id);
            }
        });
    }

    /**
     * Generate a unique transfer number.
     *
     * @param int $companyId
     * @return string
     */
    protected static function generateTransferNumber(int $companyId): string
    {
        $prefix = 'STF-' . str_pad($companyId, 4, '0', STR_PAD_LEFT);
        $timestamp = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }
}
