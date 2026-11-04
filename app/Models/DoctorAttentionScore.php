<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DoctorAttentionScore - table `doctor_attention_scores` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class DoctorAttentionScore extends Model
{
    protected $table = 'doctor_attention_scores';

    protected $fillable = [
        'doctor_id',
        'business_id',
        'branch_id',
        'calculated_date',
        'attention_score',
        'decline_percentage',
        'days_inactive',
        'last_referral_date',
        'baseline_referrals',
        'current_period_referrals',
        'previous_period_referrals',
        'status',
        'alert_reason',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'calculated_date' => 'date',
        'attention_score' => 'decimal:2',
        'decline_percentage' => 'decimal:4',
        'days_inactive' => 'integer',
        'last_referral_date' => 'date',
        'baseline_referrals' => 'decimal:4',
        'current_period_referrals' => 'integer',
        'previous_period_referrals' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
