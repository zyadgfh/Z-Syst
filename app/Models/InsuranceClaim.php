<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasFactory;

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
        'metadata' => 'array',
        'line_items' => 'array',
        'service_date' => 'date',
        'submission_date' => 'date',
        'settlement_date' => 'date',
        'total_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'patient_responsibility' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function insurancePolicy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }
}
