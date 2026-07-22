<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * StockMovement Model
 *
 * Records every stock change: sales, purchases, adjustments, transfers, returns.
 * This is the single source of truth for inventory audit trail.
 *
 * @property string $id (UUID)
 * @property string $company_id
 * @property string $branch_id
 * @property string $product_id
 * @property string|null $batch_number
 * @property string $movement_type (in, out, adjustment, transfer, return, damage, expired)
 * @property string|null $reference_type (sale, purchase, purchase_return, sale_return, stock_transfer, adjustment)
 * @property string|null $reference_id (UUID of the reference record)
 * @property float $quantity
 * @property float $quantity_before
 * @property float $quantity_after
 * @property float $cost_price
 * @property float $unit_cost
 * @property float $total_cost
 * @property string|null $from_branch_id
 * @property string|null $to_branch_id
 * @property string|null $notes
 * @property string $performed_by (user ID)
 * @property Carbon $performed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StockMovement extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_movements';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'company_id',
        'branch_id',
        'product_id',
        'batch_number',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'cost_price',
        'unit_cost',
        'total_cost',
        'from_branch_id',
        'to_branch_id',
        'notes',
        'performed_by',
        'performed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'branch_id' => 'string',
        'product_id' => 'string',
        'quantity' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'cost_price' => 'decimal:3',
        'unit_cost' => 'decimal:3',
        'total_cost' => 'decimal:3',
        'performed_at' => 'datetime',
    ];

    // ────────────────────────────── Relationships ──────────────────────────────

    /**
     * Get the company that owns this movement.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Get the branch where this movement occurred.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    /**
     * Get the product that was moved.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the user who performed this movement.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'id');
    }

    /**
     * Get the source branch (for transfers).
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'id');
    }

    /**
     * Get the destination branch (for transfers).
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'id');
    }

    // ────────────────────────────── Scopes ──────────────────────────────

    /**
     * Scope a query to only include movements of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope a query to only include movements for a specific product.
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include movements for a specific branch.
     */
    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include movements within a date range.
     */
    public function scopeWithinPeriod($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('performed_at', [$start, $end]);
    }

    /**
     * Scope a query to order by performed_at descending (most recent first).
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('performed_at', 'desc');
    }
}

