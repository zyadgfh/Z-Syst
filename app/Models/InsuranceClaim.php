<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InsuranceClaim - table `insurance_claims` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class InsuranceClaim extends Model
{
    protected $table = 'insurance_claims';

    protected $fillable = [
        'business_id',
        'insurance_company_id',
        'insurance_policy_id',
        'sale_id',
        'prescription_id',
        'customer_id',
        'claim_number',
        'service_date',
        'submission_date',
        'total_amount',
        'covered_amount',
        'patient_responsibility',
        'approved_amount',
        'paid_amount',
        'rejected_amount',
        'status',
        'rejection_reason',
        'external_reference',
        'settlement_date',
        'notes',
        'line_items',
        'metadata',
    ];

    protected $casts = [
        'insurance_company_id' => 'integer',
        'insurance_policy_id' => 'integer',
        'sale_id' => 'integer',
        'prescription_id' => 'integer',
        'customer_id' => 'integer',
        'service_date' => 'date',
        'submission_date' => 'date',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:4',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'settlement_date' => 'date',
        'line_items' => 'array',
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

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
