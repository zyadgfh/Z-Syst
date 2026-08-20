<?php

namespace App\Models;

use App\Services\InvoiceNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

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
        'tax_amount',
        'paidAmount',
        'totalAmount',
        'lossProfit',
        'paymentType',
        'invoiceNumber',
        'saleDate',
        'sale_data',
        'meta',
        'status',
    ];

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
            if (! $model->invoiceNumber && auth()->check()) {
                $invoiceNumberService = app(InvoiceNumberService::class);
                $model->invoiceNumber = $invoiceNumberService->generateSaleInvoiceNumber(auth()->user()->business_id);
            }
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
