<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory, HasUuids;

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
     * Disable timestamps (sale_items uses UUID without timestamps).
     */
    public $timestamps = false;

    protected static function newFactory()
    {
        return SaleItemFactory::new();
    }

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'inventory_id',
        'batch_number',
        'prescription_item_id',
        'name_snapshot',
        'quantity',
        'unit_price',
        'cost_price',
        'discount',
        'tax_rate',
        'tax_amount',
        'total',
        'line_total',
        'is_gift',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'sale_id' => 'string',
        'product_id' => 'string',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'cost_price' => 'decimal:3',
        'discount' => 'decimal:3',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:3',
        'total' => 'decimal:3',
        'line_total' => 'decimal:3',
        'is_gift' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the inventory record this sale item was allocated from.
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
