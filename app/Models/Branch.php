<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch - table `branches` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class Branch extends Model
{
    protected $table = 'branches';

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'address',
        'phone',
        'manager_user_id',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'manager_user_id');
    }

    public function appUsers(): HasMany
    {
        return $this->hasMany(AppUser::class, 'branch_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'branch_id');
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'branch_id');
    }

    public function controlledSubstancesLogs(): HasMany
    {
        return $this->hasMany(ControlledSubstancesLog::class, 'branch_id');
    }

    public function doctorActivitys(): HasMany
    {
        return $this->hasMany(DoctorActivity::class, 'branch_id');
    }

    public function doctorAttentionAlerts(): HasMany
    {
        return $this->hasMany(DoctorAttentionAlert::class, 'branch_id');
    }

    public function doctorAttentionScores(): HasMany
    {
        return $this->hasMany(DoctorAttentionScore::class, 'branch_id');
    }

    public function doctorAttentionSettings(): HasMany
    {
        return $this->hasMany(DoctorAttentionSetting::class, 'branch_id');
    }

    public function purchaseBudgets(): HasMany
    {
        return $this->hasMany(PurchaseBudget::class, 'branch_id');
    }

    public function qualityReports(): HasMany
    {
        return $this->hasMany(QualityReport::class, 'branch_id');
    }

    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class, 'branch_id');
    }

    public function supplierInvoicePayments(): HasMany
    {
        return $this->hasMany(SupplierInvoicePayment::class, 'branch_id');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class, 'branch_id');
    }
}
