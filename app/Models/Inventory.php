<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Model
 *
 * يمثل المخزون على مستوى الدفعة (Batch-level inventory per branch)
 * يُستخدم مع FEFO (First Expiry, First Out) لتخصيص المخزون
 *
 * @property string $id (UUID)
 * @property string $company_id
 * @property string $branch_id
 * @property string $product_id
 * @property string|null $batch_number
 * @property string|null $expiry_date
 * @property string|null $manufacturing_date
 * @property float $quantity
 * @property float $reserved_quantity
 * @property float $available_quantity (stored generated)
 * @property float $cost_price
 * @property float $selling_price
 * @property string|null $rack_location
 * @property string|null $supplier_id
 * @property string|null $purchase_order_id
 * @property string|null $grn_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property \Illuminate\Support\Carbon|null $last_moved_at
 */
class Inventory extends Model
{
    use HasUuids, HasCompany;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

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
        'expiry_date',
        'manufacturing_date',
        'quantity',
        'reserved_quantity',
        'cost_price',
        'selling_price',
        'rack_location',
        'supplier_id',
        'purchase_order_id',
        'grn_id',
        'status',
        'received_at',
        'last_moved_at',
    ];

    /**
     * Disable timestamps (we manage created_at/last_moved_at manually).
     */
    public $timestamps = false;

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
        'reserved_quantity' => 'decimal:3',
        'cost_price' => 'decimal:3',
        'selling_price' => 'decimal:3',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
        'received_at' => 'datetime',
        'last_moved_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'available_quantity',
    ];

    // ────────────────────────────── Relationships ──────────────────────────────

    /**
     * Get the product this inventory belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get the company that owns this inventory.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /**
     * Get the branch this inventory belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    /**
     * Get the supplier of this batch.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    // ────────────────────────────── Accessors ──────────────────────────────

    /**
     * Get the available quantity (not reserved).
     */
    public function getAvailableQuantityAttribute(): float
    {
        return (float) ($this->quantity - ($this->reserved_quantity ?? 0));
    }

    // ────────────────────────────── Scopes ──────────────────────────────

    /**
     * Scope: Only available (non-exhausted) inventory.
     */
    public function scopeAvailable($query): void
    {
        $query->where('quantity', '>', 0)
            ->whereColumn('quantity', '>', 'reserved_quantity');
    }

    /**
     * Scope: Not expired.
     */
    public function scopeNotExpired($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now()->toDateString());
        });
    }

    /**
     * Scope: FEFO order (nearest expiry first, then oldest received).
     */
    public function scopeFefoOrder($query): void
    {
        $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date', 'asc')
            ->orderBy('received_at', 'asc')
            ->orderBy('created_at', 'asc');
    }

    // ────────────────────────────── Actions ──────────────────────────────

    /**
     * Reserve a quantity from this batch.
     *
     * @throws \RuntimeException
     */
    public function reserve(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Reserve quantity must be positive.');
        }

        $currentReserved = (float) ($this->reserved_quantity ?? 0);
        $newReserved = $currentReserved + $quantity;

        if ($newReserved > $this->quantity) {
            throw new \RuntimeException(
                "Cannot reserve {$quantity} units. " .
                "Available: {$this->quantity}, Already reserved: {$currentReserved}"
            );
        }

        $this->updateQuietly(['reserved_quantity' => $newReserved]);
    }

    /**
     * Release reserved quantity.
     */
    public function releaseReserve(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Release quantity must be positive.');
        }

        $newReserved = max(0, ($this->reserved_quantity ?? 0) - $quantity);
        $this->updateQuietly(['reserved_quantity' => $newReserved]);
    }

    /**
     * Deduct quantity (actual consumption after sale).
     */
    public function deductQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Deduction quantity must be positive.');
        }

        $currentAvailable = (float) ($this->quantity - ($this->reserved_quantity ?? 0));
        if ($quantity > $currentAvailable) {
            throw new \RuntimeException(
                "Cannot deduct {$quantity} units. Available (not reserved): {$currentAvailable}"
            );
        }

        $newQuantity = $this->quantity - $quantity;
        $newReserved = max(0, ($this->reserved_quantity ?? 0) - $quantity);

        $updateData = [
            'quantity' => $newQuantity,
            'reserved_quantity' => $newReserved,
            'last_moved_at' => now(),
        ];

        if ($newQuantity <= 0) {
            $updateData['quantity'] = 0;
            $updateData['status'] = 'exhausted';
        }

        $this->update($updateData);
    }

    /**
     * Add quantity (for returns or restocking).
     */
    public function addQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Addition quantity must be positive.');
        }

        $newQuantity = $this->quantity + $quantity;

        $updateData = [
            'quantity' => $newQuantity,
            'last_moved_at' => now(),
        ];

        if ($this->status === 'exhausted') {
            $updateData['status'] = 'available';
        }

        $this->update($updateData);
    }

    /**
     * Check if this batch has any available stock.
     */
    public function hasAvailableStock(): bool
    {
        return $this->quantity > ($this->reserved_quantity ?? 0);
    }
}
