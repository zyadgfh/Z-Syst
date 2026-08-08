<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\InsuranceClaimFactory::new();
    }

    protected $fillable = [
        'business_id',
        'insurance_company_id',
        'insurance_policy_id',
        'sale_id',
        'prescription_id',
        'customer_id',
        'user_id',
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
        'service_date' => 'date',
        'submission_date' => 'date',
        'settlement_date' => 'date',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'line_items' => 'json',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sale::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Prescription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Party::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted', 'under_review']);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'partially_approved']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'under_review', 'approved', 'partially_approved', 'rejected', 'paid']);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'partially_approved', 'paid']);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function calculateCoverage(): array
    {
        $policy = $this->policy;
        if (!$policy) {
            return [
                'covered_amount' => 0,
                'patient_responsibility' => $this->total_amount,
                'coverage_percent' => 0,
            ];
        }

        $coveragePercent = $policy->coverage_percent ?? $policy->company->default_coverage_percent;
        $coveredAmount = ($this->total_amount * $coveragePercent) / 100;
        $patientResponsibility = $this->total_amount - $coveredAmount;

        return [
            'covered_amount' => $coveredAmount,
            'patient_responsibility' => $patientResponsibility,
            'coverage_percent' => $coveragePercent,
        ];
    }
}
