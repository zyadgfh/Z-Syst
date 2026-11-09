<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantScoped;

class DueCollect extends Model
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
        'sale_id',
        'purchase_id',
        'invoice_number',
        'totalDue',
        'dueAmountAfterPay',
        'payDueAmount',
        'payment_type',
        'paymentDate',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $id = DueCollect::where('business_id', auth()->user()->business_id)->count() + 1;
            $model->invoice_number = 'D-'.str_pad($id, 5, '0', STR_PAD_LEFT);
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
