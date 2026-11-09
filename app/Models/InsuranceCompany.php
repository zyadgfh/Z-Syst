<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InsuranceCompany - table `insurance_companies` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class InsuranceCompany extends Model
{
    
    use TenantScoped;
use HasFactory;

    protected $table = 'insurance_companies';

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
        'default_coverage_percent' => 'decimal:4',
        'default_copay_percent' => 'decimal:4',
        'settlement_days' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = ['api_credentials'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'insurance_company_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(\App\Models\InsuranceClaim::class, 'insurance_company_id');
    }

    public function calculateDefaultCoverage(float $total_amount): float
    {
        $percent = (float) ($this->default_coverage_percent ?? 0);

        return round($total_amount * $percent / 100, 2);
    }
}

