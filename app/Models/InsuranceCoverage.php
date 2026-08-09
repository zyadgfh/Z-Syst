<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('insurance_company_id', $companyId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->where('scope', 'all')
                ->orWhere('scope', 'product')
                ->where('product_id', $productId);
        });
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->where('scope', 'all')
                ->orWhere('scope', 'category')
                ->where('category_id', $categoryId);
        });
    }

    public function isEffective(): bool
    {
        $now = now();
        $fromValid = ! $this->effective_from || $this->effective_from <= $now;
        $toValid = ! $this->effective_to || $this->effective_to >= $now;

        return $this->is_active && $fromValid && $toValid;
    }

    public function calculateCoverage(float $amount): array
    {
        $coveredAmount = ($amount * $this->coverage_percent) / 100;
        $copayAmount = ($amount * $this->copay_percent) / 100;
        $patientResponsibility = $amount - $coveredAmount + $copayAmount;

        // Apply per-claim limit
        if ($this->max_amount_per_claim && $coveredAmount > $this->max_amount_per_claim) {
            $coveredAmount = $this->max_amount_per_claim;
            $patientResponsibility = $amount - $coveredAmount + $copayAmount;
        }

        return [
            'covered_amount' => $coveredAmount,
            'copay_amount' => $copayAmount,
            'patient_responsibility' => $patientResponsibility,
            'coverage_percent' => $this->coverage_percent,
            'copay_percent' => $this->copay_percent,
        ];
    }
}
