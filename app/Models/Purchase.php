<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
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
        'tax_amount',
        'due_amount',
        'paid_amount',
        'total_amount',
        'invoice_number',
        'is_paid',
        'payment_type',
        'purchase_date',
        'purchase_data',
        'note',
    ];

    public function details()
    {
        return $this->hasMany(PurchaseDetails::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function supplierInvoice()
    {
        return $this->hasOne(SupplierInvoice::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->invoice_number && auth()->check()) {
                $id = Purchase::where('business_id', auth()->user()->business_id)->count() + 1;
                $model->invoice_number = 'P-'.str_pad($id, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_paid' => 'boolean',
        'discount_amount' => 'double',
        'due_amount' => 'double',
        'paid_amount' => 'double',
        'total_amount' => 'double',
        'purchase_data' => 'json',
    ];
}
