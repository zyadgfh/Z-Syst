<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasCompany;

class InsurancePlan extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'insurance_company_id',
        'name',
        'code',
        'coverage_percentage',
        'max_coverage',
        'annual_limit',
        'co_pay',
        'requires_pre_approval',
        'covered_items',
        'exclusions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'coverage_percentage' => 'decimal:2',
        'max_coverage' => 'decimal:2',
        'annual_limit' => 'decimal:2',
        'co_pay' => 'decimal:2',
        'requires_pre_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }
}