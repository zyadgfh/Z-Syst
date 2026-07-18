<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer Model for CRM
 * 
 * Enhanced customer management with credit control and loyalty system
 */
class Customer extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        
        // Personal Info
        'name',
        'phone',
        'email',
        'address',
        
        // Customer Type
        'customer_type', // individual, pharmacy, clinic, hospital
        'customer_group',
        
        // Credit Control
        'credit_limit',
        'current_balance',
        'outstanding_balance',
        
        // Insurance
        'insurance_company_id',
        'insurance_policy_number',
        'insurance_expiry_date',
        
        // Loyalty
        'loyalty_points',
        'loyalty_tier', // bronze, silver, gold, platinum
        
        // Medical Info (for patients)
        'date_of_birth',
        'gender',
        'blood_group',
        'allergies',
        'medical_history',
        'insurance_info',
        
        // Emergency Contact
        'emergency_contact_name',
        'emergency_contact_phone',
        
        // Status
        'is_active',
        'is_tax_exempt',
        
        // Notes & Metadata
        'notes',
        'meta',
        
        // Audit
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'loyalty_points' => 'integer',
        'date_of_birth' => 'date',
        'insurance_expiry_date' => 'date',
        'is_active' => 'boolean',
        'is_tax_exempt' => 'boolean',
        'allergies' => 'json',
        'medical_history' => 'json',
        'insurance_info' => 'json',
        'meta' => 'json',
    ];

    /**
     * Get the company that owns this customer.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this customer belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the insurance company for this customer.
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    /**
     * Get sales for this customer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get prescriptions for this customer.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get loyalty transactions.
     */
    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Get the creator of this customer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater of this customer.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if customer has exceeded credit limit.
     */
    public function hasExceededCredit(): bool
    {
        return $this->outstanding_balance > $this->credit_limit;
    }

    /**
     * Get total sales amount.
     */
    public function getTotalSalesAttribute(): float
    {
        return $this->sales()->sum('total_amount');
    }

    /**
     * Get customer tier badge.
     */
    public function getTierBadgeAttribute(): string
    {
        return match($this->loyalty_tier ?? 'bronze') {
            'platinum' => '🌟 Platinum',
            'gold' => '🏆 Gold',
            'silver' => '🥈 Silver',
            default => '🥉 Bronze',
        };
    }

    /**
     * Add loyalty points.
     */
    public function addLoyaltyPoints(int $points, string $reason = 'purchase'): void
    {
        $this->loyalty_points += $points;
        $this->update(['loyalty_points' => $this->loyalty_points]);

        // Create transaction record
        $this->loyaltyTransactions()->create([
            'points' => $points,
            'type' => 'earned',
            'reason' => $reason,
        ]);
    }

    /**
     * Scope: Search customers.
     */
    public function scopeSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('phone', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope: Active customers only.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope: Top customers by sales.
     */
    public function scopeTopBySales($query, int $limit = 10)
    {
        return $query->withSum('sales', 'total_amount')
            ->orderByDesc('sales_sum_total_amount')
            ->limit($limit);
    }

    /**
     * Scope: Customers needing follow-up (declining).
     */
    public function scopeNeedFollowUp($query): void
    {
        // Customers who haven't purchased in 90 days or have declining trend
        $query->where('is_active', true)
            ->whereDoesntHave('sales', function ($q) {
                $q->where('created_at', '>', now()->subDays(90));
            });
    }
}