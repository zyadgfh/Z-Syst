<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * StockBatch Model
 *
 * Represents a batch of products received from a supplier.
 * Tracks batch number, expiry date, quantity available, and pricing.
 * Used for FEFO (First Expiry, First Out) inventory management.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $branch_id
 * @property int $product_id
 * @property int|null $warehouse_id
 * @property int|null $supplier_id
 * @property string|null $batch_number
 * @property Carbon|null $expiry_date
 * @property float $quantity_available
 * @property float $cost_price
 * @property float $purchase_price
 * @property Carbon|null $received_at
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class StockBatch extends Model
{
    use HasCompany, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'product_id',
        'warehouse_id',
        'supplier_id',
        'batch_number',
        'expiry_date',
        'quantity_available',
        'cost_price',
        'purchase_price',
        'received_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
        'quantity_available' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    /**
     * The model's default values.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'active',
    ];

    // ────────────────────────────── Relationships ──────────────────────────────

    /**
     * Get the company that owns this stock batch.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns this stock batch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product of this stock batch.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse where this stock batch is stored.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the supplier of this stock batch.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // ────────────────────────────── Scopes ──────────────────────────────

    /**
     * Scope a query to only include active batches.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include expired batches.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only include batches expiring within a given number of days.
     */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereBetween('expiry_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    /**
     * Scope a query to only include batches for a specific product.
     */
    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to order by expiry date ascending (FEFO - First Expiry, First Out).
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc');
    }

    /**
     * Scope a query to filter by batch number.
     */
    public function scopeByBatchNumber(Builder $query, string $batchNumber): Builder
    {
        return $query->where('batch_number', $batchNumber);
    }

    /**
     * Scope a query to filter by supplier.
     */
    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    // ────────────────────────────── Methods ──────────────────────────────

    /**
     * Check if this batch is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if this batch is expiring soon (within given days).
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Check if this batch has stock available.
     */
    public function hasStock(): bool
    {
        return $this->quantity_available > 0 && $this->status === 'active';
    }

    /**
     * Decrease available quantity.
     */
    public function deductQuantity(float $quantity): static
    {
        $this->decrement('quantity_available', $quantity);

        if ($this->quantity_available <= 0) {
            $this->update(['status' => 'exhausted']);
        }

        return $this;
    }

    /**
     * Increase available quantity.
     */
    public function addQuantity(float $quantity): static
    {
        $this->increment('quantity_available', $quantity);

        if ($this->status === 'exhausted' && $this->quantity_available > 0) {
            $this->update(['status' => 'active']);
        }

        return $this;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get a human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => __('Active'),
            'exhausted' => __('Exhausted'),
            'expired' => __('Expired'),
            'quarantined' => __('Quarantined'),
            'returned' => __('Returned'),
            default => ucfirst($this->status),
        };
    }
}

