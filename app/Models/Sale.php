<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
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
        'discountAmount',
        'dueAmount',
        'isPaid',
        "tax_amount",
        'paidAmount',
        'totalAmount',
        'lossProfit',
        'paymentType',
        'invoiceNumber',
        'saleDate',
        'sale_data',
        'meta',
    ];

    public function details()
    {
        return $this->hasMany(SaleDetails::class);
    }

    public function tax() : BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function party() : BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user() : BelongsTo
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
            $id = Sale::where('business_id', auth()->user()->business_id)->count() + 1;
            $model->invoiceNumber = "S-" . str_pad($id, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'discountAmount' => 'double',
        'dueAmount' => 'double',
        'isPaid' => 'boolean',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'paidAmount' => 'double',
        'totalAmount' => 'double',
        'meta' => 'json',
        'sale_data' => 'json',
    ];
}
