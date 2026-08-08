<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'product_id',
        'productStock',
        'batch_no',
        'expire_date',
        'barcode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expire_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get barcodes for the stock batch.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'batch_id');
    }

    /**
     * Get active barcodes for the stock batch.
     */
    public function activeBarcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'batch_id')->where('is_active', true);
    }
}
