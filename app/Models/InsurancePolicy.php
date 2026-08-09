<?php

namespace App\Models;

use Database\Factories\InsurancePolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return InsurancePolicyFactory::new();
    }

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
        'holder_dob' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'annual_limit' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'remaining_limit' => 'decimal:2',
        'coverage_percent' => 'decimal:2',
        'copay_percent' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function coverages(): HasMany
    {
        return $this->hasMany(InsuranceCoverage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    public function getRemainingLimitAttribute(): float
    {
        if ($this->annual_limit) {
            return max(0, $this->annual_limit - $this->used_amount);
        }

        return 0;
    }

    public function hasSufficientLimit(float $amount): bool
    {
        $remaining = $this->getRemainingLimitAttribute();

        return $remaining >= $amount;
    }
}
