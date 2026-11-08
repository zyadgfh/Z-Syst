<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InsurancePolicy - table `insurance_policies` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class InsurancePolicy extends Model
{
    use HasFactory;

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

    public function isValid($at = null): bool
    {
        $at = $at ?: now();
        if (is_string($at)) { $at = \Illuminate\Support\Carbon::parse($at); }
        $active = ($this->status ?? 'active') === 'active';
        $inWindow = true;
        if ($this->start_date !== null) { $inWindow = $inWindow && $this->start_date->lte($at); }
        if ($this->end_date !== null) { $inWindow = $inWindow && $this->end_date->gte($at); }
        return $active && $inWindow;
    }

    public function hasSufficientLimit(float $amount): bool
    {
        $limit = (float) ($this->annual_limit ?? 0);
        if ($limit <= 0) { return true; }

        return ($limit - (float) ($this->used_amount ?? 0)) >= $amount;
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }
}
