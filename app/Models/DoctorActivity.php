<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DoctorActivity - table `doctor_activities` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class DoctorActivity extends Model
{
    protected $table = 'doctor_activities';

    protected $fillable = [
        'doctor_id',
        'business_id',
        'branch_id',
        'medical_rep_id',
        'activity_date',
        'referral_count',
        'referral_amount',
        'prescription_count',
        'details',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'activity_date' => 'date',
        'referral_count' => 'integer',
        'referral_amount' => 'decimal:2',
        'prescription_count' => 'integer',
        'details' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'medical_rep_id');
    }
}
