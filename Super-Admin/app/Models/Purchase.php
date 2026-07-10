<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'party_id',
        'business_id',
        'branch_id',
        'discountAmount',
        'discount_percent',
        'discount_type',
        'shipping_charge',
        'dueAmount',
        'paidAmount',
        'totalAmount',
        'invoiceNumber',
        'vat_id',
        'vat_amount',
        'vat_percent',
        'isPaid',
        'paymentType',
        'payment_type_id',
        'purchaseDate',
        'change_amount',
    ];

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

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseDetails::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function payment_type(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('platform', 'purchase');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $id = Purchase::where('business_id', auth()->user()?->business_id ?? 1)->count() + 1;
            $model->invoiceNumber = "P" . str_pad($id, 2, '0', STR_PAD_LEFT);
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'party_id' => 'integer',
        'payment_type_id' => 'integer',
        'business_id' => 'integer',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'vat_id' => 'integer',
        'isPaid' => 'boolean',
        'discountAmount' => 'double',
        'dueAmount' => 'double',
        'paidAmount' => 'double',
        'change_amount' => 'double',
        'totalAmount' => 'double',
        'vat_amount' => 'double',
        'vat_percent' => 'double',
        'discount_percent' => 'double',
        'shipping_charge' => 'double',
    ];
}
