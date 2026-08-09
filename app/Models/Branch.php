<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'branch_name',
        'branch_code',
        'address',
        'phone',
        'email',
        'is_active',
        'location_lat',
        'location_lng',
        'settings',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'json',
        'is_active' => 'boolean',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
    ];

    /**
     * Get the company that owns the branch.
     */
    public function company()
    {
        return $this->belongsTo(Business::class, 'company_id');
    }

    /**
     * Get the payment gateways for the branch.
     */
    public function paymentGateways()
    {
        return $this->hasMany(CompanyPaymentGateway::class, 'branch_id');
    }

    /**
     * Get the transactions for the branch.
     */
    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'branch_id');
    }

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
