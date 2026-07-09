<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'product_id',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'dispensed_quantity',
        'instructions',
        'substitution_allowed',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'dispensed_quantity' => 'decimal:2',
        'substitution_allowed' => 'boolean',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
