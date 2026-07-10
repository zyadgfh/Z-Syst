<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'productName',
        'business_id',
        'unit_id',
        'brand_id',
        'vat_id',
        'vat_type',
        'vat_amount',
        'profit_percent',
        'category_id',
        'productCode',
        'productPicture',
        'productDealerPrice',
        'productPurchasePrice',
        'productSalePrice',
        'productWholeSalePrice',
        'productStock',
        'alert_qty',
        'expire_date',
        'size',
        'meta',
        'color',
        'weight',
        'capacity',
        'productManufacturer',
        'model_id',
        'warehouse_id',
        'product_type',
        'rack_id',
        'shelf_id',
        'variation_ids',
        'has_serial',
        'warranty_guarantee_info',
    ];

    public function saleDetails()
    {
        return $this->hasMany(SaleDetails::class, 'product_id', 'id');
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetails::class, 'product_id', 'id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function combo_products()
    {
        return $this->hasMany(ComboProduct::class, 'product_id', 'id')->with('stock');
    }

    public function product_model()
    {
        return $this->belongsTo(ProductModel::class, 'model_id', 'id');
    }

    public function vat()
    {
        return $this->belongsTo(Vat::class);
    }

    public function batch()
    {
        return $this->hasOne(Stock::class)
            ->where('productStock', '>', 0)
            ->latestOfMany();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'model_id' => 'integer',
        'warehouse_id' => 'integer',
        'business_id' => 'integer',
        'unit_id' => 'integer',
        'brand_id' => 'integer',
        'vat_id' => 'integer',
        'category_id' => 'integer',
        'productDealerPrice' => 'double',
        'productPurchasePrice' => 'double',
        'productSalePrice' => 'double',
        'productWholeSalePrice' => 'double',
        'productStock' => 'double',
        'vat_amount' => 'double',
        'profit_percent' => 'double',
        'alert_qty' => 'double',
        'stocks_sum_product_stock' => 'double',
        'meta' => 'json',
        'variation_ids' => 'json',
        'warranty_guarantee_info' => 'json',
    ];
}
