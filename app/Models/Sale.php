<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use TenantScoped;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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
        'shift_id',
        'client_uuid',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function details()
    {
        return $this->hasMany(SaleDetails::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->invoice_number && auth()->check()) {
                $id = Sale::where('business_id', auth()->user()->business_id)->count() + 1;
                $model->invoice_number = 'S-'.str_pad($id, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    public function scopeForBusiness(Builder $q, int $businessId): Builder { return $q->where('business_id', $businessId); }

    protected $casts = [
        'sale_date' => 'datetime',
        'discount_amount' => 'double',
        'due_amount' => 'double',
        'is_paid' => 'boolean',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'paid_amount' => 'double',
        'total_amount' => 'double',
        'meta' => 'json',
        'sale_data' => 'json',
    ];
}
