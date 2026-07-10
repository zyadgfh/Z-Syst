<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PaymentType extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'balance',
        'status',
        'opening_balance',
        'opening_date',
        'show_in_invoice',
        'meta',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    // uncomment fromTransactions, toTransactions, paymentTransactions if needed otherwise remove.
//    public function fromTransactions()
//    {
//        return $this->hasMany(Transaction::class, 'from_bank')->withTrashed();
//    }
//
//    public function toTransactions()
//    {
//        return $this->hasMany(Transaction::class, 'to_bank')->withTrashed();
//    }
//
//    public function paymentTransactions()
//    {
//        return $this->hasMany(Transaction::class, 'payment_type_id')->withTrashed();
//    }

    public function transactions()
    {
        return Transaction::where('payment_type_id', $this->id)
            ->orWhere('from_bank', $this->id)
            ->orWhere('to_bank', $this->id)
            ->withTrashed();
    }

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        static::addGlobalScope('excludeCashCheque', function ($builder) {
            $builder->whereNotIn(DB::raw('LOWER(name)'), ['cash', 'cheque']);
        });

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
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    protected $casts = [
        'business_id' => 'integer',
        'branch_id' => 'integer',
        'status' => 'integer',
        'show_in_invoice' => 'integer',
        'balance' => 'double',
        'opening_balance' => 'double',
        'meta' => 'json',
    ];
}
