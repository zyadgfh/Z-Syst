<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class InsuranceCoverage extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'insurance_company_id',
        'product_id',
        'category_id',
        'coverage_code',
        'scope',
        'coverage_percent',
        'copay_percent',
        'max_amount_per_claim',
        'max_amount_per_year',
        'requires_preauthorization',
        'is_active',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'coverage_percent' => 'decimal:2',
        'copay_percent' => 'decimal:2',
        'max_amount_per_claim' => 'decimal:2',
        'max_amount_per_year' => 'decimal:2',
        'requires_preauthorization' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isEffective(?Carbon $onDate = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = $onDate ?? now();

        if ($this->effective_from && $this->effective_from->greaterThan($date)) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->lessThan($date)) {
            return false;
        }

        return true;
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
