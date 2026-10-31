<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prescription - table `prescriptions` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Prescription extends Model
{
    protected $table = 'prescriptions';

    protected $fillable = [
        'business_id',
        'sale_id',
        'party_id',
        'image',
        'notes',
        'status',
        'prescription_number',
        'review_status',
        'review_notes',
        'reviewed_at',
        'expires_at',
        'patient_name',
        'patient_phone',
        'doctor_name',
        'doctor_license',
        'used_at',
        'meta',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'party_id' => 'integer',
        'reviewed_at' => 'datetime',
        'expires_at' => 'date',
        'used_at' => 'datetime',
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }
}
