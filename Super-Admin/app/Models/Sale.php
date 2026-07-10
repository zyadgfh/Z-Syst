<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
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
        'branch_id',
        'user_id',
        'discountAmount',
        'discount_percent',
        'discount_type',
        'shipping_charge',
        'dueAmount',
        'isPaid',
        'vat_amount',
        'vat_percent',
        'vat_id',
        'paidAmount',
        'lossProfit',
        'totalAmount',
        'paymentType',
        'payment_type_id',
        'invoiceNumber',
        'saleDate',
        'image',
        'meta',
        'rounding_option',
        'rounding_amount',
        'actual_total_amount',
        'change_amount',
        'type',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function details()
    {
        return $this->hasMany(SaleDetails::class);
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

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class);
    }

    public function payment_type(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function dueCollect()
    {
        return $this->hasOne(DueCollect::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('platform', 'sale');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $id = Sale::where('business_id', auth()->user()?->business_id ?? 1)->count() + 1;
            $model->invoiceNumber = "S" . str_pad($id, 2, '0', STR_PAD_LEFT);
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        if (auth()->check() && auth()->user()->accessToMultiBranch()) {
            static::addGlobalScope('withBranch', function ($builder) {
                $builder->with('branch:id,name');
            });
        }
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'business_id' => 'integer',
        'payment_type_id' => 'integer',
        'party_id' => 'integer',
        'branch_id' => 'integer',
        'user_id' => 'integer',
        'vat_id' => 'integer',
        'discountAmount' => 'double',
        'dueAmount' => 'double',
        'isPaid' => 'boolean',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'paidAmount' => 'double',
        'change_amount' => 'double',
        'totalAmount' => 'double',
        'lossProfit' => 'double',
        'shipping_charge' => 'double',
        'rounding_amount' => 'double',
        'actual_total_amount' => 'double',
        'discount_percent' => 'double',
        'meta' => 'json',
    ];
}
