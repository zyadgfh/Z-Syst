<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sale - table `sales` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
        'business_id',
        'party_id',
        'user_id',
        'tax_id',
        'discount_amount',
        'due_amount',
        'is_paid',
        'tax_amount',
        'paid_amount',
        'total_amount',
        'loss_profit',
        'payment_type',
        'invoice_number',
        'sale_date',
        'sale_data',
        'meta',
        'idempotency_key',
    ];

    protected $casts = [
        'party_id' => 'integer',
        'tax_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'tax_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'loss_profit' => 'decimal:2',
        'sale_date' => 'datetime',
        'sale_data' => 'array',
        'meta' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function controlledSubstancesLogs(): HasMany
    {
        return $this->hasMany(ControlledSubstancesLog::class, 'sale_id');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'sale_id');
    }

    public function fefoLogs(): HasMany
    {
        return $this->hasMany(FefoLog::class, 'sale_id');
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'sale_id');
    }

    public function prescriptionRefills(): HasMany
    {
        return $this->hasMany(PrescriptionRefill::class, 'sale_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'sale_id');
    }

    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }
}
