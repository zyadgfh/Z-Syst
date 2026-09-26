<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use BelongsToBusiness;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_id',
        'license_number',
        'rating',
        'performance_score',
        'payment_terms',
        'credit_limit',
        'contract_start',
        'contract_end',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'contract_start' => 'datetime',
        'contract_end' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(SupplierRating::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function performance(): HasMany
    {
        return $this->hasMany(SupplierPerformance::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculatePerformanceScore(): void
    {
        $latestPerformance = $this->performance()->latest()->first();
        if ($latestPerformance) {
            $this->performance_score = (
                $latestPerformance->on_time_delivery_rate * 0.4 +
                $latestPerformance->quality_score * 0.3 +
                $latestPerformance->price_competitiveness * 0.2 +
                $latestPerformance->responsiveness * 0.1
            );
            $this->save();
        }
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->ratings()->avg('rating') ?? 0;
    }
}
