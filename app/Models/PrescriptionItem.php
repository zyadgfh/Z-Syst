<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'prescription_id',
        'product_id',
        'business_id',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'quantity',
        'dispensed_quantity',
        'dispensed',
        'dispensed_at',
        'dispensed_by',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'dispensed' => 'boolean',
        'dispensed_at' => 'datetime',
        'quantity' => 'integer',
        'dispensed_quantity' => 'integer',
    ];

    /**
     * Get the prescription that owns the item.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the product for this prescription item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the business that owns the prescription item.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the user who dispensed the item.
     */
    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    /**
     * Check if the item can be dispensed.
     */
    public function canBeDispensed(): bool
    {
        return !$this->dispensed && $this->quantity > 0;
    }

    /**
     * Get the remaining quantity to dispense.
     */
    public function getRemainingQuantity(): int
    {
        return $this->quantity - $this->dispensed_quantity;
    }
}