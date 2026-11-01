<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InsurancePolicy - table `insurance_policies` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class InsurancePolicy extends Model
{
    protected $table = 'insurance_policies';

    protected $fillable = [
        'business_id',
        'insurance_company_id',
        'customer_id',
        'policy_number',
        'member_id',
        'card_number',
        'holder_name',
        'holder_dob',
        'holder_gender',
        'holder_phone',
        'holder_email',
        'holder_address',
        'plan_type',
        'status',
        'start_date',
        'end_date',
        'annual_limit',
        'used_amount',
        'remaining_limit',
        'coverage_percent',
        'copay_percent',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'insurance_company_id' => 'integer',
        'customer_id' => 'integer',
        'holder_dob' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'annual_limit' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'remaining_limit' => 'decimal:2',
        'coverage_percent' => 'decimal:4',
        'copay_percent' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }
}
