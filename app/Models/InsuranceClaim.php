<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceClaim extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'claim_number',
        'sale_id',
        'insurance_company_id',
        'insurance_plan_id',
        'patient_id',
        'branch_id',
        'amount_claimed',
        'amount_approved',
        'co_pay_amount',
        'settlement_amount',
        'status',
        'notes',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'settled_at',
        'submitted_by',
        'approved_by',
    ];

    protected $casts = [
        'amount_claimed' => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'co_pay_amount' => 'decimal:2',
        'settlement_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function insurancePlan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
