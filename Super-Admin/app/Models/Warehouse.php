<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'phone',
        'email',
        'address'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    protected static function booted()
    {
        if (function_exists('moduleCheck') && moduleCheck('MultiBranchAddon')) {
            static::addGlobalScope(new BranchScope);
        }

        if (auth()->check() && auth()->user()->accessToMultiBranch()) {
            static::addGlobalScope('withBranch', function ($builder) {
                $builder->with('branch:id,name');
            });
        }
    }

    public function transferProducts()
    {
        return $this->hasMany(TransferProduct::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    protected $casts = [
        'business_id' => 'integer',
        'branch_id' => 'integer',
    ];

}
