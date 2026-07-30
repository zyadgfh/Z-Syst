<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class InsurancePolicy extends Model
{
    use HasFactory;

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
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'holder_dob' => 'date',
        'annual_limit' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'remaining_limit' => 'decimal:2',
        'coverage_percent' => 'decimal:2',
        'copay_percent' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function isValid(?Carbon $onDate = null): bool
    {
        $date = $onDate ?? now();

        return $this->status === 'active'
            && $this->start_date->lessThanOrEqualTo($date)
            && $this->end_date->greaterThanOrEqualTo($date);
    }

    public function getRemainingLimitAttribute(): float
    {
        if ($this->annual_limit === null) {
            return 0.0;
        }

        return (float) $this->annual_limit - (float) $this->used_amount;
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
