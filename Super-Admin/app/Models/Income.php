<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'income_category_id',
        'business_id',
        'branch_id',
        'user_id',
        'amount',
        'incomeFor',
        'paymentType',
        'payment_type_id',
        'referenceNo',
        'note',
        'incomeDate',
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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->branch_id = auth()->user()->branch_id ?? auth()->user()->active_branch_id;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function payment_type(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('platform', 'income');
    }

    protected $casts = [
        'amount' => 'double',
        'income_category_id' => 'integer',
        'user_id' => 'integer',
        'business_id' => 'integer',
        'payment_type_id' => 'integer',
        'branch_id' => 'integer',
    ];
}
