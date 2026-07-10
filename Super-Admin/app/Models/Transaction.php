<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'platform',
        'transaction_type',
        'type',
        'amount',
        'date',
        'business_id',
        'branch_id',
        'payment_type_id',
        'user_id',
        'from_bank',
        'to_bank',
        'reference_id',
        'invoice_no',
        'image',
        'note',
        'meta',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class)->withTrashed();
    }

    public function fromBank()
    {
        return $this->belongsTo(PaymentType::class, 'from_bank')->withTrashed();
    }

    public function toBank()
    {
        return $this->belongsTo(PaymentType::class, 'to_bank')->withTrashed();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id');
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'reference_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'reference_id');
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'reference_id');
    }

    public function dueCollect()
    {
        return $this->belongsTo(DueCollect::class, 'reference_id');
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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $id = Transaction::where('business_id', auth()->user()?->business_id ?? 1)->count() + 1;
            $model->invoice_no = "T" . str_pad($id, 2, '0', STR_PAD_LEFT);
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    protected $casts = [
        'business_id' => 'integer',
        'branch_id' => 'integer',
        'payment_type_id' => 'integer',
        'user_id' => 'integer',
        'from_bank' => 'integer',
        'to_bank' => 'integer',
        'reference_id' => 'integer',
        'amount' => 'double',
        'meta' => 'json',
    ];
}
