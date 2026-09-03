<?php

namespace App\Models;

use App\Services\InvoiceNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DueCollect extends Model
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
        'sale_id',
        'purchase_id',
        'invoiceNumber',
        'totalDue',
        'dueAmountAfterPay',
        'payDueAmount',
        'paymentType',
        'paymentDate',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->invoiceNumber && auth()->check()) {
                $invoiceNumberService = app(InvoiceNumberService::class);
                $model->invoiceNumber = $invoiceNumberService->generateDueCollectInvoiceNumber(auth()->user()->business_id);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'party_id' => 'integer',
        'payDueAmount' => 'double',
    ];
}
