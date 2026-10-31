<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrescriptionRefill - table `prescription_refills` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PrescriptionRefill extends Model
{
    protected $table = 'prescription_refills';

    protected $fillable = [
        'business_id',
        'prescription_id',
        'refill_number',
        'refill_date',
        'quantity_dispensed',
        'dispensed_by',
        'sale_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'prescription_id' => 'integer',
        'refill_number' => 'integer',
        'refill_date' => 'date',
        'quantity_dispensed' => 'integer',
        'sale_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
