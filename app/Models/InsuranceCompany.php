<?php

namespace App\Models;

use Database\Factories\InsuranceCompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return InsuranceCompanyFactory::new();
    }

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'tax_id',
        'status',
        'integration_type',
        'api_endpoint',
        'api_credentials',
        'default_coverage_percent',
        'default_copay_percent',
        'settlement_days',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'api_credentials' => 'encrypted:array',
        'default_coverage_percent' => 'decimal:2',
        'default_copay_percent' => 'decimal:2',
        'metadata' => 'json',
    ];

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Calculate default coverage for a given amount
     */
    public function calculateDefaultCoverage(float $amount): array
    {
        $coveredAmount = ($amount * $this->default_coverage_percent) / 100;
        $patientResponsibility = $amount - $coveredAmount;

        return [
            'covered_amount' => $coveredAmount,
            'patient_responsibility' => $patientResponsibility,
            'coverage_percent' => $this->default_coverage_percent,
            'copay_percent' => $this->default_copay_percent,
        ];
    }
}
