<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'batch_no',
        'productStock',
        'productPurchasePrice',
        'profit_percent',
        'productSalePrice',
        'productWholeSalePrice',
        'productDealerPrice',
        'mfg_date',
        'expire_date',
        'variant_name',
        'variation_data',
        'serial_numbers'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'business_id' => 'integer',
        'branch_id' => 'integer',
        'warehouse_id' => 'integer',
        'product_id' => 'integer',
        'productStock' => 'double',
        'productPurchasePrice' => 'double',
        'profit_percent' => 'double',
        'productSalePrice' => 'double',
        'productWholeSalePrice' => 'double',
        'productDealerPrice' => 'double',
        'variation_data' => 'json',
        'serial_numbers' => 'json'
    ];
}
