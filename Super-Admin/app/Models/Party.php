<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'due',
        'image',
        'status',
        'address',
        'branch_id',
        'business_id',
        'credit_limit',
        'loyalty_points',
        'wallet',
        'opening_balance',
        'opening_balance_type',
        'billing_address',
        'shipping_address',
        'meta',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function canBeDeleted(): bool
    {
        // Party cannot be deleted if it has sales or purchases
        if ($this->sales()->exists() || $this->purchases()->exists()) {
            return false;
        }

        // Party cannot be deleted if due != opening_balance or wallet != 0
        if ($this->due != $this->opening_balance || $this->wallet != 0) {
            return false;
        }

        return true;
    }

    public function sales_dues() : HasMany
    {
        return $this->hasMany(Sale::class)->where('dueAmount', '>', 0);
    }

    public function purchases_dues() : HasMany
    {
        return $this->hasMany(Purchase::class)->where('dueAmount', '>', 0);
    }

    public function dueCollect()
    {
        return $this->hasOne(DueCollect::class);
    }
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'due' => 'double',
        'wallet' => 'double',
        'business_id' => 'integer',
        'status' => 'integer',
        'meta' => 'json',
        'credit_limit' => 'double',
        'loyalty_points' => 'double',
        'opening_balance' => 'double',
        'billing_address' => 'json',
        'shipping_address' => 'json',
    ];
}
