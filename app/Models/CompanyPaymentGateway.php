<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyPaymentGateway extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'gateway_type',
        'is_active',
        'config_data',
        'branch_config_data',
        'transaction_fee',
        'transaction_fee_type',
        'sort_order',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'config_data' => 'json',
        'branch_config_data' => 'json',
        'is_active' => 'boolean',
        'transaction_fee' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the company that owns the payment gateway.
     */
    public function company()
    {
        return $this->belongsTo(Business::class, 'company_id');
    }

    /**
     * Get the branch that owns the payment gateway.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the transactions for this payment gateway.
     */
    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'gateway_id');
    }

    /**
     * Scope a query to only include active gateways.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by gateway type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('gateway_type', $type);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get the effective configuration (branch config overrides company config).
     */
    public function getEffectiveConfigAttribute()
    {
        if ($this->branch_config_data && ! empty($this->branch_config_data)) {
            return array_merge($this->config_data ?? [], $this->branch_config_data);
        }

        return $this->config_data ?? [];
    }

    /**
     * Calculate transaction fee for a given amount.
     */
    public function calculateFee($amount)
    {
        if ($this->transaction_fee_type === 'percentage') {
            return ($amount * $this->transaction_fee) / 100;
        }

        return $this->transaction_fee;
    }

    /**
     * Gateway type constants.
     */
    const GATEWAY_VODAFONE_CASH = 'vodafone_cash';

    const GATEWAY_BANK_CARD = 'bank_card';

    const GATEWAY_FAWRY = 'fawry';

    const GATEWAY_ORANGE_CASH = 'orange_cash';

    const GATEWAY_INSTAPAY = 'instapay';

    const GATEWAY_CASH = 'cash';

    /**
     * Get available gateway types.
     */
    public static function getGatewayTypes()
    {
        return [
            self::GATEWAY_VODAFONE_CASH => 'Vodafone Cash',
            self::GATEWAY_BANK_CARD => 'Bank Card',
            self::GATEWAY_FAWRY => 'Fawry',
            self::GATEWAY_ORANGE_CASH => 'Orange Cash',
            self::GATEWAY_INSTAPAY => 'InstaPay',
            self::GATEWAY_CASH => 'Cash',
        ];
    }

    /**
     * Get gateway type label.
     */
    public function getGatewayTypeLabelAttribute()
    {
        return self::getGatewayTypes()[$this->gateway_type] ?? $this->gateway_type;
    }
}
