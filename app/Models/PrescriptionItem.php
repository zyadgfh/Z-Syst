<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrescriptionItem - table `prescription_items` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PrescriptionItem extends Model
{
    protected $table = 'prescription_items';

    protected $fillable = [
        'business_id',
        'prescription_id',
        'product_id',
        'medicine_name_extracted',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'notes',
        'is_approved',
    ];

    protected $casts = [
        'prescription_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'is_approved' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
