<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturn extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'branch_id',
        'purchase_id',
        'invoice_no',
        'return_date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $id = PurchaseReturn::where('business_id', auth()->user()?->business_id ?? 1)->count() + 1;
            $model->invoice_no = "PR" . str_pad($id, 2, '0', STR_PAD_LEFT);
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

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id')
            ->where('platform', 'purchase_return');
    }

    protected $casts = [
        'business_id' => 'integer',
        'purchase_id' => 'integer',
        'branch_id' => 'integer',
    ];
}
