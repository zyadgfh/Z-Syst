<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetails extends Model
{
    use TenantScoped;
    use HasFactory;

    protected $table = 'sale_details';

    /**
     * `sale_details.business_id` is NOT NULL in the live Supabase schema, and
     * bulk `SaleDetails::insert()` calls bypass model events entirely. Fill the
     * tenant from the parent sale for every Eloquent create path (relation
     * creates, queue jobs, seeders) so an omitted business_id can never produce
     * a constraint violation — or worse, a row invisible to its own tenant.
     */
    protected static function booted(): void
    {
        static::creating(function (self $detail) {
            if (empty($detail->business_id) && $detail->sale_id) {
                $detail->business_id = Sale::where('id', $detail->sale_id)->value('business_id');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'sale_id',
        'product_id',
        'price',
        'loss_profit',
        'batch_no',
        'quantities',
        'dose',
        'dose_code',
        'purchase_price',
        'expire_date',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
