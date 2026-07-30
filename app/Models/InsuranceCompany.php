<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCompany extends Model
{
    use HasFactory;

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
        'metadata' => 'array',
        'api_credentials' => 'encrypted:array',
        'default_coverage_percent' => 'decimal:2',
        'default_copay_percent' => 'decimal:2',
        'settlement_days' => 'integer',
    ];

    protected $hidden = [
        'api_credentials',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function coverages(): HasMany
    {
        return $this->hasMany(InsuranceCoverage::class);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
