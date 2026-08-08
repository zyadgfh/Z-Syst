<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantPaymentSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'gateway_id',
        'settings',
        'is_active',
        'merchant_phone',
        'merchant_name',
        'merchant_code',
        'merchant_key',
        'merchant_instapay_id',
        'bank_name',
        'account_number',
        'branch_name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tenant that owns the payment setting.
     */
    public function tenant()
    {
        return $this->belongsTo(Business::class, 'tenant_id');
    }

    /**
     * Get the branch that owns the payment setting.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the gateway for this setting.
     */
    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'gateway_id');
    }

    /**
     * Get branch relationship dynamically
     */
    public function getBranch()
    {
        // Try to find branch by ID - will need to be adjusted based on actual Branch model
        try {
            return \App\Models\Branch::find($this->branch_id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scope a query to only include active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get payment settings for a specific tenant, fallback to default.
     */
    public static function getSettingsForTenant($tenantId, $gatewayId, $branchId = null)
    {
        $query = self::where('tenant_id', $tenantId)
                     ->where('gateway_id', $gatewayId)
                     ->active();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereNull('branch_id');
        }

        $settings = $query->first();

        // Fallback to tenant-level settings if branch-specific not found
        if (!$settings && $branchId) {
            $settings = self::where('tenant_id', $tenantId)
                          ->where('gateway_id', $gatewayId)
                          ->whereNull('branch_id')
                          ->active()
                          ->first();
        }

        return $settings;
    }
}